<?php

namespace App\Notifications\Concerns;

/**
 * In-app (database) notifications shown on the Notifications page and dashboard.
 *
 * The Notifications page renders `message` as HTML, so every value that came from
 * a user (names, reasons) MUST go through e() before it is placed in the message.
 */
trait InAppAlert
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, message: string, url: ?string, kind: string}
     */
    public function toArray(object $notifiable): array
    {
        $url = $this->url();
        $message = $this->message();

        if ($url) {
            $message .= ' <a href="'.e($url).'" class="font-medium text-mustGreen underline">Open</a>';
        }

        return [
            'title' => $this->title(),
            'message' => $message,
            'url' => $url,
            'kind' => $this->kind(),
        ];
    }

    abstract protected function title(): string;

    /**
     * HTML-safe message (escape every user-supplied value with e()).
     */
    abstract protected function message(): string;

    abstract protected function url(): ?string;

    abstract protected function kind(): string;
}
