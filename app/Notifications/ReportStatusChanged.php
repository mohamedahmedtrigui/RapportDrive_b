<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

class ReportStatusChanged extends Notification
{
    public function __construct(private Report $report, private string $previousStatut) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_status_changed',
            'report_id' => $this->report->id,
            'titre' => $this->report->titre,
            'previous_statut' => $this->previousStatut,
            'statut' => $this->report->statut,
            'message' => sprintf(
                'Le statut de ton rapport « %s » est passé à « %s »',
                $this->report->titre,
                $this->report->statut,
            ),
        ];
    }
}
