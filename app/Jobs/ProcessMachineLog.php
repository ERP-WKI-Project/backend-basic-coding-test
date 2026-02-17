<?php

namespace App\Jobs;

use App\DTOs\MachineLogDto;
use App\Services\MachineLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMachineLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $dto;
    public $displayName;

    /**
     * Create a new job instance.
     */
    public function __construct(MachineLogDto $dto, ?string $customName = null)
    {
        $this->dto = $dto;
        $this->displayName = $customName ?? 'ProcessMachineLog';
    }

    /**
     * Execute the job.
     */
    public function handle(MachineLogService $service): void
    {
        $service->addLog($this->dto);
    }

    public function tags(): array
    {
        return [$this->displayName];
    }
}
