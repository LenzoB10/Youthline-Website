<?php

namespace App\Notifications;

use App\Notifications\Concerns\InAppAlert;
use Illuminate\Notifications\Notification;

class DailyReminderDigest extends Notification
{
    use InAppAlert;

    /**
     * @param  array<int, array{text: string, url: string}>  $lines
     */
    public function __construct(public array $lines) {}

    protected function title(): string
    {
        return 'Today\'s reminders';
    }

    protected function message(): string
    {
        $items = collect($this->lines)
            ->map(fn (array $line) => '<li><a href="'.e($line['url']).'" class="text-mustGreen underline">'.e($line['text']).'</a></li>')
            ->join('');

        return '<ul class="list-disc pl-5">'.$items.'</ul>';
    }

    protected function url(): ?string
    {
        return route('dashboard');
    }

    protected function kind(): string
    {
        return 'daily_reminders';
    }
}
