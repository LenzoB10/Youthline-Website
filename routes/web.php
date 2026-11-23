<?php

use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\Admin\Settings\AcademicYearController;
use App\Http\Controllers\Admin\Settings\ExitReasonController;
use App\Http\Controllers\Admin\Settings\InstitutionController;
use App\Http\Controllers\Admin\Settings\ItemCategoryController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Settings\SystemSettingController;
use App\Http\Controllers\Admin\Settings\TermController;
use App\Http\Controllers\Admin\Website\ContactInfoController;
use App\Http\Controllers\Admin\Website\ContactMessageController;
use App\Http\Controllers\Admin\Website\CoreValueController;
use App\Http\Controllers\Admin\Website\InvolvementRequestController;
use App\Http\Controllers\Admin\Website\NewsletterSubscriberController;
use App\Http\Controllers\Admin\Website\NewsPostController;
use App\Http\Controllers\Admin\Website\PagesController;
use App\Http\Controllers\Admin\Website\PartnerController;
use App\Http\Controllers\Admin\Website\ProgramController;
use App\Http\Controllers\Admin\Website\SiteSettingController;
use App\Http\Controllers\Admin\Website\TeamMemberController;
use App\Http\Controllers\Admin\Website\TestimonialController;
use App\Http\Controllers\Admin\Website\WebsiteDashboardController;
use App\Http\Controllers\BackupsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\ApprovalController;
use App\Http\Controllers\Finance\UnpaidFeesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\Sponsors\SponsorController;
use App\Http\Controllers\Sponsors\SponsorshipController;
use App\Http\Controllers\Students\AcademicResultController;
use App\Http\Controllers\Students\ConsentController;
use App\Http\Controllers\Students\EnrollmentController;
use App\Http\Controllers\Students\FeeController;
use App\Http\Controllers\Students\PaymentController;
use App\Http\Controllers\Students\PromotionController;
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentDocumentController;
use App\Http\Controllers\Students\StudentStatusController;
use App\Http\Controllers\Support\BasicNeedsController;
use App\Http\Controllers\Support\BusinessController;
use App\Http\Controllers\Support\CareerController;
use App\Http\Controllers\Support\DistributionController;
use App\Http\Controllers\Support\FamilySupportController;
use App\Http\Controllers\Support\HomeVisitController;
use App\Http\Controllers\Support\HouseholdController;
use App\Http\Controllers\Support\MentorController;
use App\Http\Controllers\Support\MentorshipController;
use App\Http\Controllers\Support\PhotoController;
use App\Http\Controllers\Support\SupportOverviewController;
use App\Http\Controllers\UserRolesAndPermissionsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\InvolvementController;
use App\Http\Controllers\Website\NewsController;
use App\Http\Controllers\Website\NewsletterController;
use App\Http\Controllers\Website\PageController;
use App\Http\Controllers\Website\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (no authentication)
|--------------------------------------------------------------------------
*/
Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/about', 'about')->name('about');
    Route::get('/programs', 'programs')->name('programs');
    Route::get('/programs/{program:slug}', 'program')->name('programs.show');
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-many-as-read', [NotificationsController::class, 'markManyAsRead'])->name('notifications.markManyAsRead');
    Route::delete('/notifications/{id}', [NotificationsController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationsController::class, 'destroyAll'])->name('notifications.destroyAll');

    // Students (Development Plan Phase 2) — Manager + Admin, per-action permissions
    Route::get('/students/promote', [PromotionController::class, 'index'])->name('students.promote.index');
    Route::post('/students/promote', [PromotionController::class, 'store'])->name('students.promote.store');
    Route::resource('students', StudentController::class);
    Route::prefix('students/{student}')->name('students.')->scopeBindings()->group(function () {
        Route::put('/status', [StudentStatusController::class, 'update'])->name('status.update');
        Route::put('/enrollment', [EnrollmentController::class, 'update'])->name('enrollment.update');
        Route::put('/consent', [ConsentController::class, 'update'])->name('consent.update');
        Route::post('/documents', [StudentDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}', [StudentDocumentController::class, 'show'])->name('documents.show');
        Route::delete('/documents/{document}', [StudentDocumentController::class, 'destroy'])->name('documents.destroy');

        // Academics & fees (Development Plan Phase 3)
        Route::post('/results', [AcademicResultController::class, 'store'])->name('results.store');
        Route::put('/results/{result}', [AcademicResultController::class, 'update'])->name('results.update');
        Route::delete('/results/{result}', [AcademicResultController::class, 'destroy'])->name('results.destroy');
        Route::post('/fees', [FeeController::class, 'store'])->name('fees.store');
        Route::put('/fees/{fee}', [FeeController::class, 'update'])->name('fees.update');
        Route::delete('/fees/{fee}', [FeeController::class, 'destroy'])->name('fees.destroy');
        Route::post('/fees/{fee}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::put('/fees/{fee}/payments/{payment}/error', [PaymentController::class, 'markError'])->name('payments.error');

        // Support programs (Development Plan Phase 4) — records owned by the student
        Route::post('/distributions', [DistributionController::class, 'store'])->name('distributions.store');
        Route::delete('/distributions/{distribution}', [DistributionController::class, 'destroy'])->name('distributions.destroy');
        Route::put('/mentor', [MentorshipController::class, 'assign'])->name('mentor.assign');
        Route::post('/sessions', [MentorshipController::class, 'storeSession'])->name('sessions.store');
        Route::put('/sessions/{mentorshipSession}', [MentorshipController::class, 'updateSession'])->name('sessions.update');
        Route::delete('/sessions/{mentorshipSession}', [MentorshipController::class, 'destroySession'])->name('sessions.destroy');
        Route::post('/businesses', [BusinessController::class, 'store'])->name('businesses.store');
        Route::put('/businesses/{business}', [BusinessController::class, 'update'])->name('businesses.update');
        Route::delete('/businesses/{business}', [BusinessController::class, 'destroy'])->name('businesses.destroy');
        Route::post('/businesses/{business}/checkins', [BusinessController::class, 'checkIn'])->name('businesses.checkins.store');
        Route::post('/businesses/{business}/repayments', [BusinessController::class, 'repay'])->name('businesses.repayments.store');
        Route::post('/outcomes', [CareerController::class, 'storeOutcome'])->name('outcomes.store');
        Route::delete('/outcomes/{alumniOutcome}', [CareerController::class, 'destroyOutcome'])->name('outcomes.destroy');
    });

    // Families (Support → Families): any family Youthline supports, with its visits and the support given.
    Route::get('/support/families', [HouseholdController::class, 'index'])->name('support.families');
    Route::post('/support/families', [HouseholdController::class, 'store'])->name('support.families.store');
    Route::prefix('support/families/{household}')->name('support.families.')->scopeBindings()->group(function () {
        Route::get('/', [HouseholdController::class, 'show'])->name('show');
        Route::put('/', [HouseholdController::class, 'update'])->name('update');
        Route::put('/consent', [HouseholdController::class, 'updateConsent'])->name('consent');
        Route::post('/visits', [HomeVisitController::class, 'storeVisit'])->name('visits.store');
        Route::delete('/visits/{homeVisit}', [HomeVisitController::class, 'destroyVisit'])->name('visits.destroy');
        Route::post('/support', [FamilySupportController::class, 'store'])->name('support.store');
        Route::delete('/support/{familySupport}', [FamilySupportController::class, 'destroy'])->name('support.destroy');
    });

    // Support overview pages
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/mentors', [MentorController::class, 'index'])->name('mentors.index');
        Route::post('/mentors', [MentorController::class, 'store'])->name('mentors.store');
        Route::put('/mentors/{mentor}', [MentorController::class, 'update'])->name('mentors.update');
        Route::delete('/mentors/{mentor}', [MentorController::class, 'destroy'])->name('mentors.destroy');
        Route::get('/basic-needs', [BasicNeedsController::class, 'index'])->name('basic-needs.index');
        Route::post('/basic-needs/group-handout', [BasicNeedsController::class, 'groupHandout'])->name('basic-needs.group');
        Route::get('/businesses', [SupportOverviewController::class, 'businesses'])->name('businesses');
        Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
        Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    });

    // Sponsors and sponsor reports (Development Plan Phase 5)
    Route::resource('sponsors', SponsorController::class)->except(['create', 'edit']);
    Route::prefix('sponsors/{sponsor}')->name('sponsors.')->scopeBindings()->group(function () {
        Route::post('/sponsorships', [SponsorshipController::class, 'store'])->name('sponsorships.store');
        Route::put('/sponsorships/{sponsorship}', [SponsorshipController::class, 'update'])->name('sponsorships.update');
        Route::put('/sponsorships/{sponsorship}/update', [SponsorshipController::class, 'progressUpdate'])->name('sponsorships.progress');
        Route::get('/report', [SponsorshipController::class, 'report'])->name('report');
    });

    // Reports & exports (Excel / PDF)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}.{format}', [ReportController::class, 'export'])->name('reports.export')
        ->where(['report' => '[a-z-]+', 'format' => 'xlsx|pdf']);

    // Finance: unpaid balances (Manager + Admin) and approvals (Admin)
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/unpaid-fees', [UnpaidFeesController::class, 'index'])->name('unpaid.index');
        Route::get('/unpaid-fees/export', [UnpaidFeesController::class, 'export'])->name('unpaid.export');
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::put('/approvals/scholarships/{fee}/approve', [ApprovalController::class, 'approveScholarship'])->name('approvals.scholarships.approve');
        Route::put('/approvals/scholarships/{fee}/reject', [ApprovalController::class, 'rejectScholarship'])->name('approvals.scholarships.reject');
    });

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

        // System settings & shared lists (Admin) — Development Plan Phase 1
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');

            Route::resource('institutions', InstitutionController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('years.index');
            Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('years.store');
            Route::put('/academic-years/{year}', [AcademicYearController::class, 'update'])->name('years.update');
            Route::put('/academic-years/{year}/current', [AcademicYearController::class, 'makeCurrent'])->name('years.current');
            Route::delete('/academic-years/{year}', [AcademicYearController::class, 'destroy'])->name('years.destroy');

            Route::post('/terms', [TermController::class, 'store'])->name('terms.store');
            Route::put('/terms/{term}', [TermController::class, 'update'])->name('terms.update');
            Route::put('/terms/{term}/current', [TermController::class, 'makeCurrent'])->name('terms.current');
            Route::delete('/terms/{term}', [TermController::class, 'destroy'])->name('terms.destroy');

            Route::resource('item-categories', ItemCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('exit-reasons', ExitReasonController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('/approvals', [SystemSettingController::class, 'edit'])->name('approvals.edit');
            Route::put('/approvals', [SystemSettingController::class, 'update'])->name('approvals.update');
        });

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
            Route::resource('testimonials', TestimonialController::class)->except('show');
            Route::resource('partners', PartnerController::class)->except('show');

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
