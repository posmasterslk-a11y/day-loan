<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "CUSTOMERS: " . \App\Models\Customer::count() . "\n";
echo "LOANS: " . \App\Models\Loan::count() . "\n";
echo "PAYMENTS: " . \App\Models\Payment::count() . "\n";
