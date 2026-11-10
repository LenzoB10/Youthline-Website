<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __invoke(StoreContactMessageRequest $request): RedirectResponse
    {
        if (blank($request->input('company'))) {
            ContactMessage::create($request->safe()->except('company') + ['status' => 'new']);
        }

        return back()->with('success', 'Thank you — your message has been sent. We will be in touch soon.');
    }
}
