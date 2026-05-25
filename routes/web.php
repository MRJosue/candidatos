<?php

use App\Http\Controllers\ApplicationThemeController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CvEducationController;
use App\Http\Controllers\CvExperienceController;
use App\Http\Controllers\CvProfileController;
use App\Http\Controllers\CvSkillController;
use App\Http\Controllers\CvTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicTalentProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\TalentController;
use App\Http\Controllers\TalentImportController;
use App\Http\Controllers\VacancyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/postulante/{talent:public_token}', [PublicTalentProfileController::class, 'edit'])->name('public-talents.edit');
Route::put('/postulante/{talent:public_token}', [PublicTalentProfileController::class, 'update'])->name('public-talents.update');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/gemini-test', function (Request $request) {
    abort_unless(app()->environment('local'), 404);

    $apiKey = config('services.gemini.key');
    $model = $request->query('model', config('services.gemini.cv_import_model', 'gemini-2.0-flash'));

    if (! filled($apiKey)) {
        return response()->json([
            'ok' => false,
            'error' => 'Falta GEMINI_API_KEY en .env',
        ], 500);
    }

    $response = Http::withHeaders([
        'X-goog-api-key' => $apiKey,
    ])
        ->acceptJson()
        ->timeout(20)
        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
            'contents' => [[
                'parts' => [[
                    'text' => 'Responde solo con la palabra OK',
                ]],
            ]],
        ]);

    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

    return response()->json([
        'ok' => $response->successful(),
        'status' => $response->status(),
        'model' => $model,
        'text' => $text,
        'google_response' => $response->json(),
    ], $response->successful() ? 200 : 502);
})->middleware('auth')->name('gemini.test');

Route::middleware('auth')->group(function () {
    Route::view('/precios', 'pricing')->name('pricing');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/appearance', [ProfileController::class, 'updateAppearance'])->name('profile.appearance.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->group(function () {
        Route::post('/admin/themes/import-json', [ApplicationThemeController::class, 'importJson'])->name('admin.themes.import-json');
        Route::resource('admin/themes', ApplicationThemeController::class)
            ->except('show')
            ->names('admin.themes')
            ->parameters(['themes' => 'theme']);
    });

    Route::get('/talents/import', [TalentImportController::class, 'create'])->name('talents.import');
    Route::get('/talents/import/layout', [TalentImportController::class, 'layout'])->name('talents.import.layout');
    Route::post('/talents/import/preview', [TalentImportController::class, 'preview'])->name('talents.import.preview');
    Route::post('/talents/import/store', [TalentImportController::class, 'store'])->name('talents.import.store');
    Route::post('/talents/download-cvs', [TalentController::class, 'downloadCvs'])->name('talents.download-cvs');
    Route::resource('talents', TalentController::class);
    Route::post('/talents/{talent}/applications', [JobApplicationController::class, 'storeForTalent'])->name('talents.applications.store');
    Route::get('/talents/{talent}/cv/create', [CvProfileController::class, 'createForTalent'])->name('talents.cv.create');
    Route::post('/talents/{talent}/cv', [CvProfileController::class, 'storeForTalent'])->name('talents.cv.store');
    Route::get('/applications/export', [JobApplicationController::class, 'export'])->name('applications.export');
    Route::post('/applications/download-cvs', [JobApplicationController::class, 'downloadCvs'])->name('applications.download-cvs');
    Route::resource('applications', JobApplicationController::class);
    Route::resource('companies', CompanyController::class);
    Route::resource('vacancies', VacancyController::class);

    Route::get('/cv/{cvProfile}/preview', [CvProfileController::class, 'preview'])->name('cv.preview');
    Route::get('/cv/{cvProfile}/download', [CvProfileController::class, 'download'])->name('cv.download');
    Route::post('/cv/import-document-ai/create', [CvProfileController::class, 'importDocumentForCreateWithAi'])->name('cv.import-document-ai-create');
    Route::post('/cv/{cvProfile}/import-document-ai', [CvProfileController::class, 'importDocumentWithAi'])->name('cv.import-document-ai');
    Route::post('/cv/{cvProfile}/apply-document-import', [CvProfileController::class, 'applyDocumentImport'])->name('cv.apply-document-import');
    Route::post('/cv/{cvProfile}/translate', [CvProfileController::class, 'translate'])->name('cv.translate');
    Route::put('/cv/{cvProfile}/sections', [CvProfileController::class, 'updateSections'])->name('cv.sections.update');
    Route::patch('/cv/{cvProfile}/template', [CvProfileController::class, 'updateTemplate'])->name('cv.template.update');
    Route::patch('/cv/{cvProfile}/section-order', [CvProfileController::class, 'updateSectionOrder'])->name('cv.section-order.update');
    Route::patch('/cv/{cvProfile}/talent', [CvProfileController::class, 'assignTalent'])->name('cv.talent.update');
    Route::resource('cv', CvProfileController::class)->parameters(['cv' => 'cvProfile']);

    Route::patch('/cv/{cvProfile}/experiences/reverse-order', [CvExperienceController::class, 'reverseOrder'])->name('cv.experiences.reverse-order');
    Route::resource('cv.experiences', CvExperienceController::class)
        ->shallow()
        ->parameters(['cv' => 'cvProfile', 'experiences' => 'cvExperience']);
    Route::patch('/cv/{cvProfile}/education/reverse-order', [CvEducationController::class, 'reverseOrder'])->name('cv.education.reverse-order');
    Route::resource('cv.education', CvEducationController::class)
        ->shallow()
        ->parameters(['cv' => 'cvProfile', 'education' => 'cvEducation']);
    Route::resource('cv.skills', CvSkillController::class)
        ->shallow()
        ->parameters(['cv' => 'cvProfile', 'skills' => 'cvSkill']);

    Route::get('/templates', [CvTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{template}', [CvTemplateController::class, 'show'])->name('templates.show');
    Route::post('/templates/{template}/select/{cvProfile}', [CvTemplateController::class, 'select'])->name('templates.select');

    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/templates/{template}/purchase', [PurchaseController::class, 'store'])->name('templates.purchase');

    Route::post('/appointments/{appointment}/send-invitations', [AppointmentController::class, 'sendInvitations'])->name('appointments.send-invitations');
    Route::resource('appointments', AppointmentController::class);
});

require __DIR__.'/auth.php';
