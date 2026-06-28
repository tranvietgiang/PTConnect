<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ZipArchive;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $students = Student::query()
            ->with('classroom:id,name,grade_level')
            ->when($request->filled('classroom_id'), fn ($query) => $query->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword): void {
                    $inner->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('student_code', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('full_name')
            ->get()
            ->map(fn (Student $student): array => $this->serialize($student));

        return $this->success('Students retrieved.', $students->all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'student_code' => ['required', 'string', 'max:50', Rule::unique('students', 'student_code')],
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::query()->create([
            'classroom_id' => $validated['classroom_id'],
            'student_code' => trim($validated['student_code']),
            'full_name' => trim($validated['full_name']),
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => 'studying',
        ]);

        return $this->success('Student created.', $this->serialize($student->load('classroom')), 201);
    }

    public function show(Student $student): JsonResponse
    {
        return $this->success('Student retrieved.', $this->serialize($student->load('classroom')));
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:xlsx,csv,txt'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
        ]);

        $defaultClassroomId = $validated['classroom_id'] ?? null;
        $rows = $this->readImportRows($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalExtension());
        $classrooms = Classroom::query()->get()->keyBy(fn (Classroom $classroom): string => mb_strtolower(trim($classroom->name)));
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $studentCode = trim((string) ($row['student_code'] ?? ''));
            $fullName = trim((string) ($row['full_name'] ?? ''));
            $className = mb_strtolower(trim((string) ($row['class_name'] ?? '')));
            $classroomId = $defaultClassroomId;

            if ($className !== '') {
                $classroomId = $classrooms[$className]->id ?? null;
            }

            if ($studentCode === '' || $fullName === '' || ! $classroomId) {
                $skipped++;
                $errors[] = "Dòng {$line}: thiếu mã học sinh, họ tên hoặc lớp không tồn tại.";
                continue;
            }

            if (Student::query()->where('student_code', $studentCode)->exists()) {
                $skipped++;
                $errors[] = "Dòng {$line}: mã học sinh {$studentCode} đã tồn tại.";
                continue;
            }

            Student::query()->create([
                'classroom_id' => $classroomId,
                'student_code' => $studentCode,
                'full_name' => $fullName,
                'date_of_birth' => $this->normalizeDate($row['date_of_birth'] ?? null),
                'phone' => trim((string) ($row['student_phone'] ?? '')) ?: null,
                'address' => trim((string) ($row['address'] ?? '')) ?: null,
                'status' => trim((string) ($row['status'] ?? '')) ?: 'studying',
            ]);

            $created++;
        }

        return $this->success('Student import completed.', [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 20),
        ], 201);
    }

    private function serialize(Student $student): array
    {
        return [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'full_name' => $student->full_name,
            'classroom_id' => $student->classroom_id,
            'class_name' => $student->classroom?->name,
            'date_of_birth' => $student->date_of_birth?->toDateString(),
            'address' => $student->address,
            'status' => $student->status,
        ];
    }

    private function success(string $message, array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function readImportRows(string $path, string $extension): array
    {
        if (strtolower($extension) === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        return $this->readCsvRows($path);
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            return [];
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeHeaders($data);
                continue;
            }

            $rows[] = $this->combineRow($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheetXml) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $rawRows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) $attributes['r'];
                $type = (string) $attributes['t'];
                $columnIndex = $this->columnIndexFromReference($reference);
                $value = (string) ($cell->v ?? '');

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                }

                $cells[$columnIndex] = trim($value);
            }

            if ($cells !== []) {
                ksort($cells);
                $rawRows[] = $cells;
            }
        }

        if ($rawRows === []) {
            return [];
        }

        $headers = $this->normalizeHeaders(array_values($rawRows[0]));
        $rows = [];

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $data = [];

            for ($index = 0; $index < count($headers); $index++) {
                $data[$index] = $rawRow[$index] ?? null;
            }

            $rows[] = $this->combineRow($headers, $data);
        }

        return $rows;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $sharedStrings = [];
        $strings = simplexml_load_string($xml);

        foreach ($strings->si as $item) {
            if (isset($item->t)) {
                $sharedStrings[] = (string) $item->t;
                continue;
            }

            $text = '';

            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }

            $sharedStrings[] = $text;
        }

        return $sharedStrings;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($header): string => $this->mapImportHeader((string) $header), $headers);
    }

    private function mapImportHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = str_replace([' ', '-', '.'], '_', $normalized);

        return match ($normalized) {
            'ma_hoc_sinh', 'mã_học_sinh', 'student_code', 'code' => 'student_code',
            'ho_ten', 'họ_và_tên', 'họ_tên', 'full_name', 'name' => 'full_name',
            'lop', 'lớp', 'class', 'class_name', 'ten_lop', 'tên_lớp' => 'class_name',
            'ngay_sinh', 'ngày_sinh', 'date_of_birth', 'dob' => 'date_of_birth',
            'sdt_hoc_sinh', 'sđt_học_sinh', 'student_phone', 'phone' => 'student_phone',
            'dia_chi', 'địa_chỉ', 'address' => 'address',
            'trang_thai', 'trạng_thái', 'status' => 'status',
            default => $normalized,
        };
    }

    private function combineRow(array $headers, array $data): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = $data[$index] ?? null;
        }

        return $row;
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
