<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\Admin\ActivityLogController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Api\V1\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Api\V1\Admin\PropertyImageController;
use App\Http\Controllers\Api\V1\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\EnquiryController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {

    // ---- Public routes ----
    Route::get('/home', HomeController::class);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/testimonials', [TestimonialController::class, 'index']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/featured', [PropertyController::class, 'featured']);
    Route::get('/properties/{slug}', [PropertyController::class, 'show']);
    Route::get('/properties/{slug}/similar', [PropertyController::class, 'similar']);

    Route::middleware('throttle:10,1')->post('/enquiries', [EnquiryController::class, 'store']);

    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    });

    // ---- Authenticated routes (any logged-in user) ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/profile/password', [AuthController::class, 'changePassword']);

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/{property:slug}', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{property:slug}', [FavoriteController::class, 'destroy']);

        // ---- Admin-only routes ----
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::get('/properties', [AdminPropertyController::class, 'index']);
            Route::post('/properties', [AdminPropertyController::class, 'store']);
            Route::get('/properties/{property:id}', [AdminPropertyController::class, 'show']);
            Route::put('/properties/{property:id}', [AdminPropertyController::class, 'update']);
            Route::delete('/properties/{property:id}', [AdminPropertyController::class, 'destroy']);
            Route::post('/properties/{id}/restore', [AdminPropertyController::class, 'restore']);
            Route::delete('/properties/{id}/force', [AdminPropertyController::class, 'forceDestroy']);
            Route::patch('/properties/{property:id}/status', [AdminPropertyController::class, 'updateStatus']);
            Route::patch('/properties/{property:id}/featured', [AdminPropertyController::class, 'updateFeatured']);

            Route::post('/properties/{property:id}/images', [PropertyImageController::class, 'store']);
            Route::put('/properties/{property:id}/images/{image}', [PropertyImageController::class, 'update']);
            Route::patch('/properties/{property:id}/images/{image}/cover', [PropertyImageController::class, 'setCover']);
            Route::post('/properties/{property:id}/images/reorder', [PropertyImageController::class, 'reorder']);
            Route::delete('/properties/{property:id}/images/{image}', [PropertyImageController::class, 'destroy']);

            Route::get('/enquiries/export', [AdminEnquiryController::class, 'export']);
            Route::get('/enquiries', [AdminEnquiryController::class, 'index']);
            Route::get('/enquiries/{enquiry}', [AdminEnquiryController::class, 'show']);
            Route::patch('/enquiries/{enquiry}', [AdminEnquiryController::class, 'update']);
            Route::delete('/enquiries/{enquiry}', [AdminEnquiryController::class, 'destroy']);

            Route::get('/testimonials', [AdminTestimonialController::class, 'index']);
            Route::post('/testimonials', [AdminTestimonialController::class, 'store']);
            Route::put('/testimonials/{testimonial}', [AdminTestimonialController::class, 'update']);
            Route::delete('/testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy']);
            Route::patch('/testimonials/{testimonial}/approval', [AdminTestimonialController::class, 'toggleApproval']);
            Route::post('/testimonials/reorder', [AdminTestimonialController::class, 'reorder']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::get('/users/{user}', [AdminUserController::class, 'show']);
            Route::patch('/users/{user}', [AdminUserController::class, 'update']);

            Route::get('/settings', [AdminSettingController::class, 'index']);
            Route::put('/settings', [AdminSettingController::class, 'update']);

            Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        });
    });
});
