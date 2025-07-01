<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ImportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public $fileType;

    public function __construct($fileType)
    {
        $this->fileType = $fileType;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Your ' . $this->fileType . ' import has completed.'
        ];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Your ' . $this->fileType . ' import has completed.'
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'Your ' . $this->fileType . ' import has completed.'
        ]);
    }
}
