<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Console\Command;

class AutoLockAccounts extends Command
{
    protected $signature = 'accounts:auto-lock';
    protected $description = 'Auto-lock student accounts 7 days after all courses end. Lock TA accounts when assigned courses end.';

    public function handle(): int
    {
        $this->info('Checking accounts to auto-lock...');

        $lockedStudents = 0;
        $lockedAssistants = 0;
        $sevenDaysAgo = now()->subDays(7);

        // Lock students: no active enrollments where course end_date > 7 days ago
        $expiredEnrollments = StudentEnrollment::where('status', 'active')
            ->whereHas('course', function ($q) use ($sevenDaysAgo) {
                $q->where('end_date', '<=', $sevenDaysAgo);
            })
            ->get();

        $studentIds = $expiredEnrollments->pluck('student_id')->unique();

        foreach ($studentIds as $studentId) {
            $hasActiveCourse = StudentEnrollment::where('student_id', $studentId)
                ->where('status', 'active')
                ->whereHas('course', function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>', now()->subDays(7));
                    });
                })
                ->exists();

            if (!$hasActiveCourse) {
                $studentProfile = \App\Models\StudentProfile::find($studentId);
                if ($studentProfile) {
                    User::where('id', $studentProfile->user_id)
                        ->where('is_active', true)
                        ->where('role', 'student')
                        ->update(['is_active' => false]);
                    $lockedStudents++;
                }
            }
        }

        // Lock TAs: find TAs assigned to classrooms where ALL linked courses ended 7+ days ago
        $taUserIds = User::where('role', 'assistant')->where('is_active', true)->pluck('id');

        foreach ($taUserIds as $taUserId) {
            $activeClassrooms = Classroom::where('assistant_id', $taUserId)
                ->whereHas('course', function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>', now()->subDays(7));
                    });
                })
                ->count();

            if ($activeClassrooms === 0) {
                $assignedClassrooms = Classroom::where('assistant_id', $taUserId)->count();

                if ($assignedClassrooms > 0) {
                    User::where('id', $taUserId)->update(['is_active' => false]);
                    $lockedAssistants++;
                }
            }
        }

        $this->info("Locked {$lockedStudents} student(s).");
        $this->info("Locked {$lockedAssistants} assistant(s).");

        return Command::SUCCESS;
    }
}
