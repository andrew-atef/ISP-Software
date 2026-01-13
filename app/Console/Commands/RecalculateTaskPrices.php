<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class RecalculateTaskPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:recalculate-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate tech_price for all tasks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting task price recalculation...');

        $tasks = Task::all();

        foreach ($tasks as $task) {
            // Trigger saving event where Observer runs
            $task->save();
            $this->info("Updated Task #{$task->id}: Price \${$task->tech_price}");
        }

        $this->info('Recalculation completed.');
    }
}
