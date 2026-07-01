<?php

namespace App\Repositories;

use App\Models\EmailNotification;

class EmailNotificationRepository extends Repository
{
    protected function model(): string
    {
        return EmailNotification::class;
    }
}
