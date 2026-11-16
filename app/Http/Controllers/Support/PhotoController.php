<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Services\Support\PhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Middleware('permission:manage home visits', only: ['show'])]
#[Middleware('permission:delete support records', only: ['destroy'])]
class PhotoController extends Controller
{
    public function __construct(protected PhotoService $photos) {}

    public function show(Photo $photo): StreamedResponse
    {
        abort_unless($this->photos->exists($photo), 404);

        return $this->photos->stream($photo);
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        $this->photos->delete($photo);

        return back()->with('success', 'Photo deleted.');
    }
}
