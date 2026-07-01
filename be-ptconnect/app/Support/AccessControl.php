<?php

namespace App\Support;

use App\Models\AssistantAssignment;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;

class AccessControl
{
    public static function isAdmin(?User $user): bool
    {
        return (bool) $user?->isAdmin();
    }

    public static function canAccessAssignedClassroom(?User $user, int|Classroom $classroom): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $classroomId = $classroom instanceof Classroom ? $classroom->id : (int) $classroom;

        if ($user->isTeacher()) {
            if ($classroom instanceof Classroom) {
                return (int) $classroom->teacher_id === (int) $user->id;
            }

            return Classroom::query()
                ->whereKey($classroomId)
                ->where('teacher_id', $user->id)
                ->exists();
        }

        if ($user->isAssistant()) {
            return AssistantAssignment::query()
                ->where('assistant_id', $user->id)
                ->where('classroom_id', $classroomId)
                ->where('status', AssistantAssignment::STATUS_ACTIVE)
                ->exists();
        }

        return false;
    }

    public static function assignedClassroomIds(User $user): array
    {
        if ($user->isAdmin()) {
            return Classroom::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if ($user->isTeacher()) {
            return Classroom::query()
                ->where('teacher_id', $user->id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if ($user->isAssistant()) {
            return AssistantAssignment::query()
                ->where('assistant_id', $user->id)
                ->where('status', AssistantAssignment::STATUS_ACTIVE)
                ->pluck('classroom_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return [];
    }

    public static function legacyStudentIdsForUser(User $user): array
    {
        if (! $user->isStudent()) {
            return [];
        }

        $codes = collect([
            $user->studentProfile?->student_code,
            $user->username,
        ])->filter()->unique()->values()->all();

        if ($codes === []) {
            return [];
        }

        return Student::query()
            ->whereIn('student_code', $codes)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public static function canAccessLegacyStudent(?User $user, Student $student): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher() || $user->isAssistant()) {
            return in_array((int) $student->classroom_id, self::assignedClassroomIds($user), true);
        }

        if ($user->isStudent()) {
            return in_array((int) $student->id, self::legacyStudentIdsForUser($user), true);
        }

        return false;
    }
}
