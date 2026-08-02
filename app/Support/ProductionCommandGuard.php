<?php

namespace App\Support;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;

final class ProductionCommandGuard
{
    /** @var list<string> */
    private const BLOCKED_COMMANDS = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
        'db:wipe',
    ];

    public static function register(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! app()->environment('production')) {
                return;
            }

            if (in_array($event->command, self::BLOCKED_COMMANDS, true)) {
                throw new \RuntimeException(
                    "Güvenlik: `{$event->command}` production ortamında engellendi. Veri kaybı riski taşır."
                );
            }

            if ($event->command === 'db:seed') {
                $class = (string) ($event->input->getOption('class') ?? '');

                if ($class !== '' && stripos($class, 'TestData') !== false) {
                    throw new \RuntimeException('TestDataSeeder production ortamında engellendi.');
                }
            }
        });
    }
}
