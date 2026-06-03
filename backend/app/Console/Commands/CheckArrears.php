<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckArrears extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:check-arrears';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan pending loan schedules and mark overdue ones as Arrears';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = now()->toDateString();

        $updatedCount = \App\Models\LoanSchedule::where('due_date', '<', $today)
            ->whereIn('status', ['Pending', 'Partial'])
            ->update(['status' => 'Arrears']);

        $this->info("Successfully checked and marked {$updatedCount} schedules as Arrears.");

        return Command::SUCCESS;
    }
}
