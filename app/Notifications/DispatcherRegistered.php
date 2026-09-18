<?php

namespace App\Notifications;

use App\Models\Dispatcher;
use Illuminate\Notifications\Notification;

class DispatcherRegistered extends Notification
{
    public function __construct(private Dispatcher $dispatcher) {}

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
            'type' => 'dispatcher_registered',
            'dispatcher_id' => $this->dispatcher->id,
            'nom' => $this->dispatcher->nom,
            'email' => $this->dispatcher->email,
            'message' => sprintf(
                'Nouvelle inscription dispatcher en attente : %s (%s)',
                $this->dispatcher->nom,
                $this->dispatcher->email,
            ),
        ];
    }
}
