<?php

namespace Tests\Feature;

use App\Models\Domains\Events\Event;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EventLifecycle;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EventWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => database_path('migrations/system'),
            '--realpath' => true,
        ];
    }

    public function test_event_owner_can_request_approval(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-10');

        Role::create(['name' => Roles::EVENT_OWNER, 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole(Roles::EVENT_OWNER);

        $event = Event::create([
            'name' => 'Vendor Summit',
            'status' => Event::STATUS_DRAFT,
            'requires_risk_approval' => true,
        ]);

        app(EventLifecycle::class)->requestApproval($event, $user);

        $event->refresh();

        $this->assertSame(Event::STATUS_PENDING_APPROVAL, $event->status);
        $this->assertDatabaseCount('event_approvals', 3);
        $this->assertDatabaseHas('event_status_histories', [
            'event_id' => $event->id,
            'to_status' => Event::STATUS_PENDING_APPROVAL,
        ]);

        tenancy()->end();
    }

    public function test_event_requires_all_required_approvals(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-11');

        Role::create(['name' => Roles::EVENT_OWNER, 'guard_name' => 'web']);
        Role::create(['name' => Roles::ESG_LEAD, 'guard_name' => 'web']);
        Role::create(['name' => Roles::PROCUREMENT, 'guard_name' => 'web']);

        $owner = User::factory()->create();
        $owner->assignRole(Roles::EVENT_OWNER);

        $esg = User::factory()->create();
        $esg->assignRole(Roles::ESG_LEAD);

        $procurement = User::factory()->create();
        $procurement->assignRole(Roles::PROCUREMENT);

        $event = Event::create([
            'name' => 'Annual Cleanup',
            'status' => Event::STATUS_DRAFT,
            'requires_risk_approval' => false,
        ]);

        $lifecycle = app(EventLifecycle::class);
        $lifecycle->requestApproval($event, $owner);
        $event->refresh();

        $lifecycle->recordApproval($event, $esg, Event::APPROVAL_ESG, true);
        $event->refresh();
        $this->assertSame(Event::STATUS_PENDING_APPROVAL, $event->status);

        $lifecycle->recordApproval($event, $procurement, Event::APPROVAL_PROCUREMENT, true);
        $event->refresh();
        $this->assertSame(Event::STATUS_APPROVED, $event->status);

        tenancy()->end();
    }

    public function test_user_without_role_cannot_request_approval(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->bootstrapTenant('bank-12');

        $user = User::factory()->create();

        $event = Event::create([
            'name' => 'Risk Review',
            'status' => Event::STATUS_DRAFT,
            'requires_risk_approval' => false,
        ]);

        $this->expectException(AuthorizationException::class);

        app(EventLifecycle::class)->requestApproval($event, $user);

        tenancy()->end();
    }

    private function bootstrapTenant(string $tenantId): Tenant
    {
        $tenant = Tenant::create([
            'id' => $tenantId,
            'database' => 'tenant_' . $tenantId,
        ]);

        $tenant->createDatabase();

        tenancy()->initialize($tenant);

        Artisan::call('migrate', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        return $tenant;
    }
}
