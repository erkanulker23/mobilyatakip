<?php

namespace App\Jobs;

use App\Models\XmlFeed;
use App\Services\XmlFeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncXmlFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public XmlFeed $xmlFeed,
        public bool $createSuppliers = true
    ) {}

    public function handle(XmlFeedService $xmlFeedService): void
    {
        $xmlFeedService->syncFeed($this->xmlFeed->fresh(), $this->createSuppliers);
    }
}
