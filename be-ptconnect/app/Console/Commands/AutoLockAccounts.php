<?php

namespace App\Console\Commands;

use App\Models\AssistantAssignment;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Console\Command;

class AutoLockAccounts extends Command
{
    protected $signature = 'account:auto-lock';

    protected $description = 'Auto-lock student/assistant accounts 7 days after course ends; reactivate on new enrollment';

    public function handle(): void
    {
        $this->lockStudents();
        $this->lockAssistants();
        $this->reactivateUsers();

        $this->info('Account auto-lock completed.');
    }

    private function lockStudents(): void
    {
        $sevenDaysAgo = now()->subDays(7);

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('is_active', true)
            ->whereDoesntHave('studentProfile.studentEnrollments', fn ($q) => $q->where('status', StudentEnrollment::STATUS_ACTIVE))
            ->whereHas('studentProfile.studentEnrollments', fn ($q) => $q
                ->whereIn('status', [StudentEnrollment::STATUS_COMPLETED, StudentEnrollment::STATUS_CANCELLED])
                ->where('ended_at', '<=', $sevenDaysAgo))
            ->get();

        $count = 0;
        foreach ($students as $user) {
            $hasRecent = $user->studentProfile?->studentEnrollments()
                ->whereIn('status', [StudentEnrollment::STATUS_COMPLETED, StudentEnrollment::STATUS_CANCELLED])
                ->where('ended_at', '>', $sevenDaysAgo)
                ->exists();

            if (! $hasRecent) {
                $user->update(['is_active' => false]);
                $count++;
            }
        }

        $this->info("Locked {$count} student(s) with no active enrollment for 7+ days.");
    }

    private function lockAssistants(): void
    {
        $sevenDaysAgo = now()->subDays(7);

        $assistants = User::query()
            ->where('role', User::ROLE_ASSISTANT)
            ->where('is_active', true)
            ->whereDoesntHave('assistantAssignments', fn ($q) => $q->where('status', AssistantAssignment::STATUS_ACTIVE))
            ->whereHas('assistantAssignments', fn ($q) => $q
                ->whereIn('status', [AssistantAssignment::STATUS_ENDED, AssistantAssignment::STATUS_LOCKED])
                ->where('ended_at', '<=', $sevenDaysAgo))
            ->get();

        $count = 0;
        foreach ($assistants as $user) {
            $hasRecent = $user->assistantAssignments()
                ->whereIn('status', [AssistantAssignment::STATUS_ENDED, AssistantAssignment::STATUS_LOCKED])
                ->where('ended_at', '>', $sevenDaysAgo)
                ->exists();

            if (! $hasRecent) {
                $user->update(['is_active' => false]);
                $count++;
            }
        }

        $this->info("Locked {$count} assistant(s) with no active assignment for 7+ days.");
    }

    private function reactivateUsers(): void
    {
        $reactivatedStudents = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('is_active', false)
            ->whereHas('studentProfile.studentEnrollments', fn ($q) => $q->where('status', StudentEnrollment::STATUS_ACTIVE))
            ->update(['is_active' => true]);

        if ($reactivatedStudents > 0) {
            $this->info("Reactivated {$reactivatedStudents} student(s) with new active enrollment(s).");
        }

        $reactivatedAssistants = User::query()
            ->where('role', User::ROLE_ASSISTANT)
            ->where('is_active', false)
            ->whereHas('assistantAssignments', fn ($q) => $q->where('status', AssistantAssignment::STATUS_ACTIVE))
            ->update(['is_active' => true]);

        if ($reactivatedAssistants > 0) {
            $this->info("Reactivated {$reactivatedAssistants} assistant(s) with new active assignment(s).");
        }
    }
}
