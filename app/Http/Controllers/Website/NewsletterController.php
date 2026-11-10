<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function __invoke(SubscribeNewsletterRequest $request): RedirectResponse
    {
        if (blank($request->input('company'))) {
            NewsletterSubscriber::firstOrCreate(
                ['email' => $request->validated('email')],
                ['subscribed_at' => now()],
            );
        }

        return back()->with('success', 'Thanks for subscribing to the Youthline newsletter.');
    }
}
