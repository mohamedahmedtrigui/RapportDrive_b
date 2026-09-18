<?php

namespace App\Policies;

use App\Models\Dispatcher;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class ReportPolicy
{
    public function view(Authenticatable $principal, Report $report): bool
    {
        return $this->ownsOrIsUser($principal, $report);
    }

    /**
     * Also governs adding/editing/deleting entries (ReportEntryController
     * authorizes against the parent report's 'update' ability). Once the
     * owning dispatcher has submitted the report, it becomes read-only for
     * them — the AI analysis it triggered is meant to run once, against a
     * final set of entries. Manager/admin are never subject to this lock.
     */
    public function update(Authenticatable $principal, Report $report): bool
    {
        return $this->canModify($principal, $report);
    }

    public function delete(Authenticatable $principal, Report $report): bool
    {
        return $this->canModify($principal, $report);
    }

    public function analyze(Authenticatable $principal, Report $report): bool
    {
        // The owning dispatcher triggers this once via "Soumettre le rapport"
        // when done adding entries; manager/admin can also re-trigger it.
        // The "already submitted" case is rejected with a dedicated 422
        // message in AiController rather than here, so it stays distinct
        // from a plain 403.
        return $this->ownsOrIsUser($principal, $report);
    }

    private function canModify(Authenticatable $principal, Report $report): bool
    {
        if ($principal instanceof User) {
            return true;
        }

        return $principal instanceof Dispatcher
            && $report->dispatcher_id === $principal->id
            && ! $report->submitted_at;
    }

    private function ownsOrIsUser(Authenticatable $principal, Report $report): bool
    {
        if ($principal instanceof User) {
            return true;
        }

        return $principal instanceof Dispatcher && $report->dispatcher_id === $principal->id;
    }
}
