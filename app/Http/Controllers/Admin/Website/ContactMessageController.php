<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\UpdateContactMessageStatusRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class ContactMessageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Website/Messages/Index', [
            'messages' => ContactMessage::query()
                ->latest()
                ->get(['id', 'name', 'email', 'phone', 'message', 'status', 'created_at']),
        ]);
    }

    public function update(UpdateContactMessageStatusRequest $request, ContactMessage $message): RedirectResponse
    {
        $message->update($request->validated());

        return back()->with('success', 'Message status updated.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }
}
