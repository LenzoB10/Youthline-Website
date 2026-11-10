<?php

use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\Admin\Website\ContactInfoController;
use App\Http\Controllers\Admin\Website\ContactMessageController;
use App\Http\Controllers\Admin\Website\CoreValueController;
use App\Http\Controllers\Admin\Website\InvolvementRequestController;
use App\Http\Controllers\Admin\Website\NewsletterSubscriberController;
use App\Http\Controllers\Admin\Website\NewsPostController;
use App\Http\Controllers\Admin\Website\PagesController;
use App\Http\Controllers\Admin\Website\ProgramController;
use App\Http\Controllers\Admin\Website\SiteSettingController;
use App\Http\Controllers\Admin\Website\TeamMemberController;
use App\Http\Controllers\Admin\Website\WebsiteDashboardController;
use App\Http\Controllers\BackupsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserRolesAndPermissionsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\InvolvementController;
use App\Http\Controllers\Website\NewsController;
use App\Http\Controllers\Website\NewsletterController;
use App\Http\Controllers\Website\PageController;
use App\Http\Controllers\Website\SeoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public website (no authentication)
|--------------------------------------------------------------------------
*/
Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/about', 'about')->name('about');
    Route::get('/programs', 'programs')->name('programs');
    Route::get('/impact', 'impact')->name('impact');
    Route::get('/get-involved', 'getInvolved')->name('get-involved');
    Route::get('/news', 'news')->name('news');
    Route::get('/contact', 'contact')->name('contact');
});

// Public news detail
Route::get('/news/{post}', [NewsController::class, 'show'])->name('news.show');

// Public form submissions
Route::post('/contact', ContactController::class)->name('contact.store');
Route::post('/get-involved', InvolvementController::class)->name('involvement.store');
Route::post('/newsletter', NewsletterController::class)->name('newsletter.store');

// SEO endpoints
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Email verification is disabled during development — re-add the 'verified'
// middleware here (['auth', 'verified']) to require it again.
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-many-as-read', [NotificationsController::class, 'markManyAsRead'])->name('notifications.markManyAsRead');
    Route::delete('/notifications/{id}', [NotificationsController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationsController::class, 'destroyAll'])->name('notifications.destroyAll');

    Route::name('admin.')->group(function () {
        Route::resource('users', UsersController::class);
        Route::put('user/{user}/update-role', [UserRolesAndPermissionsController::class, 'changeRole'])
            ->name('user.update-role');
        Route::put('user/{user}/update-permissions', [UserRolesAndPermissionsController::class, 'changePermissions'])
            ->name('user.update-permissions');
        Route::put('user/{user}/activate', [UsersController::class, 'activate'])
            ->name('user.activate');
        Route::put('user/{user}/ban', [UsersController::class, 'ban'])
            ->name('user.ban');

        Route::resource('roles', RolesController::class);
        Route::get('/activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');

        Route::get('/backups', [BackupsController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupsController::class, 'store'])->name('backups.store');
        Route::put('/backups/settings', [BackupsController::class, 'updateSettings'])->name('backups.update-settings');
        Route::get('/backups/{filename}/download', [BackupsController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{filename}', [BackupsController::class, 'destroy'])->name('backups.destroy');

        // Public website content management (Admin + Manager)
        Route::prefix('website')->name('website.')->group(function () {
            Route::get('/', [WebsiteDashboardController::class, 'index'])->name('index');
            Route::get('/settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SiteSettingController::class, 'update'])->name('settings.update');
            Route::get('/contact', [ContactInfoController::class, 'edit'])->name('contact.edit');
            Route::put('/contact', [ContactInfoController::class, 'update'])->name('contact.update');
            Route::get('/pages', [PagesController::class, 'index'])->name('pages.index');
            Route::get('/pages/{page}/edit', [PagesController::class, 'edit'])->name('pages.edit');
            Route::put('/pages/{page}', [PagesController::class, 'update'])->name('pages.update');
            Route::put('/news/{news}/publish', [NewsPostController::class, 'togglePublish'])->name('news.publish');
            Route::resource('news', NewsPostController::class)->except('show');

            Route::resource('programs', ProgramController::class)->except('show');
            Route::resource('team', TeamMemberController::class)->except('show');

            Route::get('/values', [CoreValueController::class, 'index'])->name('values.index');
            Route::post('/values', [CoreValueController::class, 'store'])->name('values.store');
            Route::put('/values/{value}', [CoreValueController::class, 'update'])->name('values.update');
            Route::delete('/values/{value}', [CoreValueController::class, 'destroy'])->name('values.destroy');

            // Public form submissions
            Route::get('/messages', [ContactMessageController::class, 'index'])->name('messages.index');
            Route::put('/messages/{message}', [ContactMessageController::class, 'update'])->name('messages.update');
            Route::delete('/messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');

            Route::get('/involvement', [InvolvementRequestController::class, 'index'])->name('involvement.index');
            Route::put('/involvement/{involvement}', [InvolvementRequestController::class, 'update'])->name('involvement.update');
            Route::delete('/involvement/{involvement}', [InvolvementRequestController::class, 'destroy'])->name('involvement.destroy');

            Route::get('/subscribers', [NewsletterSubscriberController::class, 'index'])->name('subscribers.index');
            Route::get('/subscribers/export', [NewsletterSubscriberController::class, 'export'])->name('subscribers.export');
            Route::delete('/subscribers/{subscriber}', [NewsletterSubscriberController::class, 'destroy'])->name('subscribers.destroy');
        });
    });
});

require __DIR__.'/auth.php';
