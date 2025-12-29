<?php

namespace App\Providers;

use App\Events\ApprovalRecorded;
use App\Events\ReportPublished;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAssigned;
use Spatie\Permission\Events\RoleRemoved;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(AuditLogger::class)->log('auth.login', [
                'guard' => $event->guard,
            ], $event->user);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            app(AuditLogger::class)->log('auth.logout', [
                'guard' => $event->guard,
            ], $event->user);
        });

        Event::listen(RoleAssigned::class, function (RoleAssigned $event): void {
            app(AuditLogger::class)->log('role.assigned', [
                'role' => $event->role->name ?? null,
                'subject_id' => $event->model->getKey(),
                'subject_type' => $event->model::class,
            ], $event->model);
        });

        Event::listen(RoleRemoved::class, function (RoleRemoved $event): void {
            app(AuditLogger::class)->log('role.removed', [
                'role' => $event->role->name ?? null,
                'subject_id' => $event->model->getKey(),
                'subject_type' => $event->model::class,
            ], $event->model);
        });

        Event::listen(ApprovalRecorded::class, function (ApprovalRecorded $event): void {
            app(AuditLogger::class)->log('approval.recorded', $event->metadata, $event->user);
        });

        Event::listen(ReportPublished::class, function (ReportPublished $event): void {
            app(AuditLogger::class)->log('report.published', $event->metadata, $event->user);
        });
    }
}
