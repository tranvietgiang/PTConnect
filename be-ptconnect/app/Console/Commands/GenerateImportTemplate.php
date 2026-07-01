<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GenerateImportTemplate extends Command
{
    protected $signature = 'template:student-import';
    protected $description = 'Generate Excel template for student import';

    public function handle(): int
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import học sinh');

        $headers = [
            'A1' => 'Họ và tên',
            'B1' => 'Giới tính',
            'C1' => 'Ngày sinh',
            'D1' => 'Số điện thoại',
            'E1' => 'Địa chỉ',
            'F1' => 'Lớp',
            'G1' => 'Trạng thái',
            'H1' => 'Tên đăng nhập',
            'I1' => 'Email',
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D9488'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getRowDimension('1')->setRowHeight(30);

        $sampleData = [
            'A2' => 'Nguyễn Văn A',
            'B2' => 'Nam',
            'C2' => '15/03/2010',
            'D2' => '0901234567',
            'E2' => '123 Đường ABC, Quận 1',
            'F2' => '10A1',
            'G2' => 'studying',
            'H2' => 'nguyenvana',
            'I2' => 'nguyenvana@email.com',
        ];

        $sampleStyle = [
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        foreach ($sampleData as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $sheet->getStyle('A2:I2')->applyFromArray($sampleStyle);

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(30);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Hướng dẫn');
        $sheet2->setCellValue('A1', 'HƯỚNG DẪN IMPORT HỌC SINH');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->setCellValue('A3', 'Các cột bắt buộc: Họ và tên, Lớp');
        $sheet2->setCellValue('A4', 'Các cột còn lại có thể để trống.');
        $sheet2->setCellValue('A5', 'Mã học sinh: Hệ thống tự động sinh theo lớp.');
        $sheet2->setCellValue('A6', 'Mật khẩu tài khoản phụ huynh: Mặc định là tên đăng nhập.');
        $sheet2->setCellValue('A7', 'Giới tính: Nam / Nữ');
        $sheet2->setCellValue('A8', 'Ngày sinh: Định dạng DD/MM/YYYY hoặc YYYY-MM-DD.');
        $sheet2->setCellValue('A9', 'Trạng thái: studying / dropped_out / graduated (mặc định studying).');
        $sheet2->setCellValue('A10', 'Tên đăng nhập & Email: Dùng cho tài khoản phụ huynh.');
        $sheet2->setCellValue('A11', 'Lớp phải tồn tại trong hệ thống (vd: 10A1, 11A2, ...).');
        $sheet2->getColumnDimension('A')->setWidth(70);

        $path = storage_path('app/templates/student-import-template.xlsx');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        $this->info("Template created: {$path}");

        return self::SUCCESS;
    }
}
