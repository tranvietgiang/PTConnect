<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScoreNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public string $className;
    public string $assignmentTitle;
    public ?string $score;
    public ?string $comment;

    public function __construct(
        string $studentName,
        string $className,
        string $assignmentTitle,
        ?string $score,
        ?string $comment = null,
    ) {
        $this->studentName = $studentName;
        $this->className = $className;
        $this->assignmentTitle = $assignmentTitle;
        $this->score = $score;
        $this->comment = $comment;
    }

    public function build(): ScoreNotification
    {
        return $this->subject('PTConnect - Thông báo điểm bài tập')
            ->view('emails.score');
    }
}
