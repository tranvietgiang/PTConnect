<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use App\Services\ScoreService;
use Illuminate\Console\Command;

class AutoSendEmails extends Command
{
    protected $signature = 'emails:auto-send';
    protected $description = 'Auto-send pending attendance and score emails.';

    public function handle(AttendanceService $attendanceService, ScoreService $scoreService): int
    {
        $this->info('Sending pending attendance emails...');
        $attendanceService->autoSendEmails();

        $this->info('Done.');

        return Command::SUCCESS;
    }
}
