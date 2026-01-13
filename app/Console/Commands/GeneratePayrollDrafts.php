<?php

namespace App\Console\Commands;

use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GeneratePayrollDrafts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate-drafts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate draft payroll records for the previous week for all technicians.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting payroll draft generation...');

        // Determine previous week
        // Assuming we run this on Sunday/Monday for the week that just ended.
        $date = Carbon::now()->subWeek();
        $year = $date->year;
        $week = $date->weekOfYear;

        $this->info("Target Period: Week $week, Year $year");

        $techs = User::where('role', UserRole::Tech)->get();

        foreach ($techs as $tech) {
            $this->info("Processing: {$tech->name}");

            $payroll = Payroll::firstOrCreate(
                [
                    'user_id' => $tech->id,
                    'week_number' => $week,
                    'year' => $year,
                ],
                [
                    'status' => PayrollStatus::Draft,
                    'gross_amount' => 0,
                    'deductions_amount' => 0,
                    'net_pay' => 0,
                ]
            );

            // Only recalculate if it's still a draft (don't touch paid ones if re-run)
            if ($payroll->status === PayrollStatus::Draft) {
                try {
                    $payroll->recalculate();
                    $this->info(" - Draft updated. Net Pay: \${$payroll->net_pay}");
                } catch (\Exception $e) {
                    $this->error(" - Error recalculating: " . $e->getMessage());
                }
            } else {
                $this->comment(" - Skipped (Status: {$payroll->status->value})");
            }
        }

        $this->info('Payroll draft generation completed.');
    }
}
