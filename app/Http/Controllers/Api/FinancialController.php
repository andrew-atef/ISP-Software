<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Task;
use App\Enums\TaskFinancialStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class FinancialController extends Controller
{
    #[OA\Get(
        path: '/api/financials/summary',
        summary: 'Get technician financial summary (earnings, loans, payroll)',
        security: [['BearerAuth' => []]],
        tags: ['Financial'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Financial summary data',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'current_week_earnings', type: 'number', format: 'float', description: 'Sum of tech_price from Approved tasks this week', example: 1500.50),
                            new OA\Property(property: 'tasks_count', type: 'integer', description: 'Count of Approved tasks this week', example: 5),
                            new OA\Property(property: 'upcoming_loan_deduction', type: 'number', format: 'float', description: 'Sum of loan installments due by next Saturday', example: 250.00),
                            new OA\Property(
                                property: 'last_payroll',
                                description: 'Latest payroll record (if exists)',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'gross_amount', type: 'number', format: 'float'),
                                    new OA\Property(property: 'deductions', type: 'number', format: 'float'),
                                    new OA\Property(property: 'bonus_amount', type: 'number', format: 'float'),
                                    new OA\Property(property: 'net_pay', type: 'number', format: 'float'),
                                    new OA\Property(property: 'status', type: 'string'),
                                    new OA\Property(property: 'week_start', type: 'string', format: 'date'),
                                    new OA\Property(property: 'week_end', type: 'string', format: 'date'),
                                    new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
                                ],
                                type: 'object',
                                nullable: true
                            ),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function summary(): JsonResponse
    {
        $user = auth()->user();
        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(); // Sunday
        $weekEnd = $now->copy()->endOfWeek(); // Saturday

        // Current week earnings from Approved tasks
        $currentWeekEarnings = Task::where('assigned_tech_id', $user->id)
            ->where('financial_status', TaskFinancialStatus::Billable)
            ->whereBetween('completion_date', [$weekStart, $weekEnd])
            ->sum('tech_price');

        // Count of Approved tasks this week
        $tasksCount = Task::where('assigned_tech_id', $user->id)
            ->where('financial_status', TaskFinancialStatus::Billable)
            ->whereBetween('completion_date', [$weekStart, $weekEnd])
            ->count();

        // Upcoming loan deductions (installments due by next Saturday)
        $nextSaturday = $weekEnd->copy()->addWeek();
        $upcomingLoanDeduction = $user->loanInstallments()
            ->where('is_paid', false)
            ->where('due_date', '<=', $nextSaturday)
            ->sum('amount');

        // Last payroll record
        $lastPayroll = Payroll::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'current_week_earnings' => (float) ($currentWeekEarnings ?? 0),
            'tasks_count' => $tasksCount,
            'upcoming_loan_deduction' => (float) ($upcomingLoanDeduction ?? 0),
            'last_payroll' => $lastPayroll ? [
                'id' => $lastPayroll->id,
                'gross_amount' => (float) $lastPayroll->gross_amount,
                'deductions' => (float) $lastPayroll->deductions,
                'bonus_amount' => (float) $lastPayroll->bonus_amount,
                'net_pay' => (float) $lastPayroll->net_pay,
                'status' => $lastPayroll->status,
                'week_start' => $lastPayroll->week_start?->format('Y-m-d'),
                'week_end' => $lastPayroll->week_end?->format('Y-m-d'),
                'paid_at' => $lastPayroll->paid_at?->format('Y-m-d\TH:i:s\Z'),
            ] : null,
        ]);
    }

    #[OA\Get(
        path: '/api/financials/loans',
        summary: 'Get all active loans with installment schedule',
        security: [['BearerAuth' => []]],
        tags: ['Financial'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active loans',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'principal', type: 'number', format: 'float'),
                                new OA\Property(property: 'interest_rate', type: 'number', format: 'float'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                                new OA\Property(
                                    property: 'installments',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'amount', type: 'number', format: 'float'),
                                            new OA\Property(property: 'due_date', type: 'string', format: 'date'),
                                            new OA\Property(property: 'is_paid', type: 'boolean'),
                                            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        )
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function loans(): JsonResponse
    {
        $loans = Loan::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['installments' => function ($query) {
                $query->orderBy('due_date');
            }])
            ->orderByDesc('created_at')
            ->get();

        $response = $loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'principal' => (float) $loan->principal,
                'interest_rate' => (float) $loan->interest_rate,
                'status' => $loan->status,
                'start_date' => $loan->start_date?->format('Y-m-d'),
                'installments' => $loan->installments->map(function ($installment) {
                    return [
                        'id' => $installment->id,
                        'amount' => (float) $installment->amount,
                        'due_date' => $installment->due_date?->format('Y-m-d'),
                        'is_paid' => (bool) $installment->is_paid,
                        'paid_at' => $installment->paid_at?->format('Y-m-d\TH:i:s\Z'),
                    ];
                }),
            ];
        });

        return response()->json($response);
    }
}
