<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendanceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $content;
    public string $studentName;
    public string $className;
    public string $date;
    public string $statusLabel;
    public string $statusColor;

    public function __construct(
        string $title,
        string $content,
        string $studentName,
        string $className,
        string $date,
        string $statusLabel,
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->studentName = $studentName;
        $this->className = $className;
        $this->date = $date;
        $this->statusLabel = $statusLabel;
        $this->statusColor = match ($statusLabel) {
            'Đi muộn' => '#d97706',
            'Vắng' => '#dc2626',
            default => '#16a34a',
        };
    }

    public function build(): AttendanceNotification
    {
        return $this->subject("PTConnect - {$this->title}")
            ->view('emails.attendance');
    }
}
