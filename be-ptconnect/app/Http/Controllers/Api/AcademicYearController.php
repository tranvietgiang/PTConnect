<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AcademicYearRepository;
use Illuminate\Http\JsonResponse;

class AcademicYearController extends Controller
{
    public function __construct(
        protected AcademicYearRepository $academicYearRepo
    ) {}

    public function index(): JsonResponse
    {
        $years = $this->academicYearRepo->newQuery()
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'data' => $years,
        ]);
    }
}
