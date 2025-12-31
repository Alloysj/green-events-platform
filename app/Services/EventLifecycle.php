<?php

namespace App\Services;

use App\Events\ApprovalRecorded;
use App\Events\ReportPublished;
use App\Models\Domains\Events\Event;
use App\Models\Domains\Events\EventApproval;
use App\Models\Domains\Events\EventStatusHistory;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use App\Services\AuditLogger;

class EventLifecycle
{
    public function requestApproval(Event $event, User $actor): void
    {
        $this->authorizeRequestApproval($actor);

        if ($event->status !== Event::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Event is not in draft.');
        }

        foreach ($event->approvalTypes() as $type) {
            EventApproval::query()->create([
                'event_id' => $event->id,
                'type' => $type,
                'status' => EventApproval::STATUS_PENDING,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);
        }

        $this->transition($event, Event::STATUS_PENDING_APPROVAL, $actor, [
            'requested_by' => $actor->id,
        ]);
    }

    public function recordApproval(Event $event, User $actor, string $type, bool $approved, ?string $notes = null): void
    {
        $this->authorizeApproval($actor, $type);

        if ($event->status !== Event::STATUS_PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Event is not pending approval.');
        }

        $approval = EventApproval::query()
            ->where('event_id', $event->id)
            ->where('type', $type)
            ->where('status', EventApproval::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();

        if (! $approval) {
            throw new \RuntimeException('No pending approval found for this type.');
        }

        $approval->status = $approved ? EventApproval::STATUS_APPROVED : EventApproval::STATUS_REJECTED;
        $approval->decided_by = $actor->id;
        $approval->decided_at = now();
        $approval->notes = $notes;
        $approval->save();

        event(new ApprovalRecorded($actor, [
            'event_id' => $event->id,
            'approval_type' => $type,
            'status' => $approval->status,
        ]));

        if (! $approved) {
            $this->transition($event, Event::STATUS_DRAFT, $actor, [
                'rejected_type' => $type,
            ]);

            return;
        }

        if ($this->allRequiredApprovalsApproved($event)) {
            $this->transition($event, Event::STATUS_APPROVED, $actor, [
                'approved_by' => $actor->id,
            ]);
        }
    }

    public function complete(Event $event, User $actor): void
    {
        $this->authorizeCompletion($actor);

        if ($event->status !== Event::STATUS_APPROVED) {
            throw new \InvalidArgumentException('Event is not approved.');
        }

        $this->transition($event, Event::STATUS_COMPLETED, $actor, [
            'completed_by' => $actor->id,
        ]);
    }

    public function publishReport(Event $event, User $actor): void
    {
        $this->authorizeReportPublish($actor);

        if ($event->status !== Event::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Event is not completed.');
        }

        $this->transition($event, Event::STATUS_REPORT_PUBLISHED, $actor, [
            'published_by' => $actor->id,
        ]);

        event(new ReportPublished($actor, [
            'event_id' => $event->id,
        ]));
    }

    private function transition(Event $event, string $toStatus, User $actor, array $metadata = []): void
    {
        $fromStatus = $event->status;

        $event->status = $toStatus;
        $event->save();

        EventStatusHistory::query()->create([
            'event_id' => $event->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $actor->id,
            'metadata' => $metadata,
        ]);

        app(AuditLogger::class)->log('event.status_changed', [
            'event_id' => $event->id,
            'from' => $fromStatus,
            'to' => $toStatus,
        ], $actor);
    }

    private function allRequiredApprovalsApproved(Event $event): bool
    {
        foreach ($event->approvalTypes() as $type) {
            $latest = EventApproval::query()
                ->where('event_id', $event->id)
                ->where('type', $type)
                ->orderByDesc('id')
                ->first();

            if (! $latest || $latest->status !== EventApproval::STATUS_APPROVED) {
                return false;
            }
        }

        return true;
    }

    private function authorizeRequestApproval(User $actor): void
    {
        if ($actor->hasAnyRole([Roles::TENANT_ADMIN, Roles::EVENT_OWNER])) {
            return;
        }

        throw new AuthorizationException('Not allowed to request approval.');
    }

    private function authorizeApproval(User $actor, string $type): void
    {
        if ($actor->hasRole(Roles::TENANT_ADMIN)) {
            return;
        }

        $map = [
            Event::APPROVAL_ESG => Roles::ESG_LEAD,
            Event::APPROVAL_PROCUREMENT => Roles::PROCUREMENT,
            Event::APPROVAL_RISK => Roles::RISK_REVIEWER,
        ];

        $requiredRole = $map[$type] ?? null;

        if ($requiredRole && $actor->hasRole($requiredRole)) {
            return;
        }

        throw new AuthorizationException('Not allowed to record approval.');
    }

    private function authorizeCompletion(User $actor): void
    {
        if ($actor->hasAnyRole([Roles::TENANT_ADMIN, Roles::EVENT_OWNER])) {
            return;
        }

        throw new AuthorizationException('Not allowed to complete event.');
    }

    private function authorizeReportPublish(User $actor): void
    {
        if ($actor->hasAnyRole([Roles::TENANT_ADMIN, Roles::ESG_LEAD])) {
            return;
        }

        throw new AuthorizationException('Not allowed to publish report.');
    }
}
