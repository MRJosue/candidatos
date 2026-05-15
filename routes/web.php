<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ApplicationThemeController;
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
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/postulante/{talent:public_token}', [PublicTalentProfileController::class, 'edit'])->name('public-talents.edit');
Route::put('/postulante/{talent:public_token}', [PublicTalentProfileController::class, 'update'])->name('public-talents.update');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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
    Route::resource('talents', TalentController::class);
    Route::post('/talents/{talent}/applications', [JobApplicationController::class, 'storeForTalent'])->name('talents.applications.store');
    Route::get('/talents/{talent}/cv/create', [CvProfileController::class, 'createForTalent'])->name('talents.cv.create');
    Route::get('/applications/export', [JobApplicationController::class, 'export'])->name('applications.export');
    Route::resource('applications', JobApplicationController::class);
    Route::resource('companies', CompanyController::class);
    Route::resource('vacancies', VacancyController::class);

    Route::get('/cv/{cvProfile}/preview', [CvProfileController::class, 'preview'])->name('cv.preview');
    Route::get('/cv/{cvProfile}/download', [CvProfileController::class, 'download'])->name('cv.download');
    Route::patch('/cv/{cvProfile}/template', [CvProfileController::class, 'updateTemplate'])->name('cv.template.update');
    Route::patch('/cv/{cvProfile}/section-order', [CvProfileController::class, 'updateSectionOrder'])->name('cv.section-order.update');
    Route::patch('/cv/{cvProfile}/talent', [CvProfileController::class, 'assignTalent'])->name('cv.talent.update');
    Route::resource('cv', CvProfileController::class)->parameters(['cv' => 'cvProfile']);

    Route::resource('cv.experiences', CvExperienceController::class)
        ->shallow()
        ->parameters(['cv' => 'cvProfile', 'experiences' => 'cvExperience']);
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

    Route::resource('appointments', AppointmentController::class);
});

require __DIR__.'/auth.php';
