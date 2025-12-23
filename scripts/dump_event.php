<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
// Bootstrap the kernel so Eloquent and config are loaded
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Event;
use App\Services\EventFinanceAggregator;

$eventId = $argv[1] ?? null;

if ($eventId) {
    $e = Event::with('payments', 'expenses')->find($eventId);
    if (!$e) {
        echo "EVENT_NOT_FOUND\n";
        exit(1);
    }
} else {
    $e = Event::with('payments', 'expenses')->first();
    if (!$e) {
        echo "NO_EVENTS\n";
        exit(1);
    }
}

$svc = app(EventFinanceAggregator::class);
$payload = $svc->artistEventFinance($e);

// Ensure event is array
if ($payload['event'] instanceof \Illuminate\Database\Eloquent\Model) {
    $payload['event'] = $payload['event']->toArray();
}

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
