<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentProfileSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'student@ptconnect.edu.vn')->first();

        if ($user) {
            StudentProfile::create([
                'user_id' => $user->id,
                'student_code' => 'HS100001',
                'full_name' => 'Nguyễn Văn A',
                'email' => 'student@ptconnect.edu.vn',
                'parent_email' => 'phuhuynh.a@example.com',
                'high_school' => 'Trường THPT Chuyên Khoa Học Tự Nhiên',
                'date_of_birth' => '2008-05-15',
                'phone' => '0901000001',
                'parent_name' => 'Nguyễn Văn B',
                'parent_phone' => '0901000002',
                'parent_relationship' => 'father',
            ]);
        }
    }
}
