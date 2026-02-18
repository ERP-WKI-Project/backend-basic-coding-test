<?php

namespace App\Observers;

use App\Models\Machine;
use Illuminate\Support\Facades\Cache;

class MachineObserver
{
    /**
     * Handle the Machines "created" event.
     */
    public function created(Machine $machine): void
    {
        Cache::tags(['machine'])->flush();
    }

    /**
     * Handle the Machines "updated" event.
     */
    public function updated(Machine $machine): void
    {
        Cache::tags(['machine'])->flush();
    }

    /**
     * Handle the Machines "deleted" event.
     */
    public function deleted(Machine $machine): void
    {
        Cache::tags(['machine'])->flush();

    }

    /**
     * Handle the Machines "restored" event.
     */
    public function restored(Machine $machine): void
    {
        //
    }

    /**
     * Handle the Machines "force deleted" event.
     */
    public function forceDeleted(Machine $machine): void
    {
        //
    }
}
