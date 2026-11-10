<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\InvolvementRequest;
use App\Models\NewsletterSubscriber;
use App\Models\NewsPost;
use App\Models\Page;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class WebsiteDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Website/Dashboard', [
            'stats' => [
                'pages' => Page::query()->count(),
                'news_published' => NewsPost::query()->where('is_published', true)->count(),
                'news_drafts' => NewsPost::query()->where('is_published', false)->count(),
                'new_messages' => ContactMessage::query()->where('status', 'new')->count(),
                'new_requests' => InvolvementRequest::query()->where('status', 'new')->count(),
                'subscribers' => NewsletterSubscriber::query()->count(),
            ],
        ]);
    }
}
