<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

class ReportSubmitted extends Notification
{
    public function __construct(private Report $report) {}

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
            'type' => 'report_submitted',
            'report_id' => $this->report->id,
            'titre' => $this->report->titre,
            'date_rapport' => $this->report->date_rapport?->toDateString(),
            'dispatcher_nom' => $this->report->dispatcher?->nom,
            'message' => sprintf(
                'Nouveau rapport soumis par %s (%s)',
                $this->report->dispatcher?->nom ?? 'un dispatcher',
                $this->report->titre,
            ),
        ];
    }
}
