<?php

namespace App\Observers;

use App\Models\Notice;
use App\Support\Notify;

class NoticeObserver
{
    /**
     * Inject the notifier helper.
     */
    public function __construct(
        private Notify $notify
    ) {}
    /**
     * Handle the Notice "created" event.
     */
    public function created(Notice $notice): void
    {
        //
    }

    /**
     * Handle the Notice "updated" event.
     */
    public function updated(Notice $notice): void
    {
        //
        if (! $notice->wasChanged()) {
            return;
        }

        $this->notify
            ->allUsers()
            ->info(
                'Edital atualizado',
                "O edital {$notice->name} foi atualizado"
            );
    }

    /**
     * Handle the Notice "deleted" event.
     */
    public function deleted(Notice $notice): void
    {
        //
    }

    /**
     * Handle the Notice "restored" event.
     */
    public function restored(Notice $notice): void
    {
        //
    }

    /**
     * Handle the Notice "force deleted" event.
     */
    public function forceDeleted(Notice $notice): void
    {
        //
    }
}
