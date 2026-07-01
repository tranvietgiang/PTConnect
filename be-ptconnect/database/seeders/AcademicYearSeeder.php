<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::create([
            'name' => 'Năm học 2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);

        AcademicYear::create([
            'name' => 'Năm học 2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-05-31',
            'is_active' => false,
        ]);
    }
}
