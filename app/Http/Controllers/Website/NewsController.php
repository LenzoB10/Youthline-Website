<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Services\Seo\SeoService;
use App\Services\Website\PublicContentService;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected PublicContentService $content,
    ) {}

    public function show(NewsPost $post): Response
    {
        abort_unless(
            NewsPost::query()->publiclyVisible()->whereKey($post->getKey())->exists(),
            404,
        );

        return Inertia::render('Public/NewsShow', [
            'seo' => $this->seo->forPage('news', [
                'title' => $post->title,
                'description' => Str::limit(strip_tags((string) $post->body), 160),
                'type' => 'article',
            ]),
            'post' => $this->content->newsPost($post),
        ]);
    }
}
