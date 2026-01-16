# ISP Software - AI Coding Agent Guide

## Project Overview
ISP field service management system built with **Laravel 12 + Filament 4 + Livewire**. Manages technician dispatch, task tracking, inventory, payroll, and invoicing for an Internet Service Provider.

## Architecture & Core Concepts

### Domain Models & Business Rules
- **Task**: Central entity with parent/child relationships. NewInstall tasks auto-generate DropBury sub-tasks when completed if `drop_bury_status=false` (see [TaskObserver](../app/Observers/TaskObserver.php))
- **Financial Model**: Tasks have dual pricing (`company_price` for invoicing, `tech_price` for payroll). `TaskObserver` auto-fills prices from `JobPrice` model ONLY if null and not manually set
- **TaskFinancialStatus**: `Billable` vs `NotBillable` determines invoice inclusion. DropBury sub-tasks are always `NotBillable` (parent billed $350, child is cost-only)
- **Payroll**: Week-based (Sunday-Saturday) with automatic loan installment deductions. Uses `Payroll::getWeekDateRange()` for ISO week calculations
- **Inventory**: Wallet-based system tracking items per technician with transaction history

### Key Enums (Backed by Strings)
- **TaskType**: `NewInstall`, `DropBury`, `ServiceCall`, `ServiceChange`
- **TaskStatus**: `Pending` → `Assigned` → `Started` → `Completed` → `Approved`
- **TaskFinancialStatus**: `Billable`, `NotBillable`

### Observers & Auto-Behaviors
- **TaskObserver**: Registered in [AppServiceProvider](../app/Providers/AppServiceProvider.php)
  - `saving()`: Auto-fills `company_price`/`tech_price` from `JobPrice` (safety net, never overwrites manual input)
  - `updated()`: Creates DropBury sub-task when NewInstall completed without drop_bury done
- **Loan**: `created()` event auto-generates installment records via `createInstallments()`
- **CompanyInvoice**: `creating()` event auto-generates invoice number with format `INV-YYYYMMWW[-##]`

## Development Workflow

### Local Development (Composer Script: `dev`)
```bash
composer dev
```
Runs 4 concurrent processes via `concurrently`:
1. `php artisan serve` - Web server (port 8000)
2. `php artisan queue:listen --tries=1` - Queue worker
3. `php artisan pail --timeout=0` - Log tailing
4. `npm run dev` - Vite HMR

### Testing
```bash
composer test
# or directly:
php artisan test
```
Uses **Pest PHP** test framework (not PHPUnit syntax). Tests in `tests/Feature/` and `tests/Unit/`.

### Setup New Environment
```bash
composer setup
```
Runs: `composer install` → copy `.env.example` → `key:generate` → `migrate` → `npm install` → `npm run build`

## Filament-Specific Conventions

### Resource Structure
- **Resources**: Located in `app/Filament/Resources/` (e.g., `TechnicianResource.php`)
- **Forms**: Use `Schema::components([...])` with `InfoSection` and form components
- **Tables**: Define columns, filters, actions in `Table` method
- **Pages**: Custom pages in `app/Filament/Pages/`
- **User Scoping**: `TechnicianResource::getEloquentQuery()` filters `Users` by `role=Tech`

### Schema Components
Use Filament's form builder with semantic sections:
```php
InfoSection::make('Identity')->columns(2)->schema([...])
```

## Code Patterns & Conventions

### Price Storage Philosophy
- **Historical Accuracy**: Never recalculate stored prices. `Task::calculateTechPay()` returns `tech_price` snapshot, not computed values
- **Source of Truth**: `JobPrice` model defines default rates per `TaskType`
- **Manual Override**: UI/forms always win over auto-fill logic

### Eloquent Relationships
- Use typed return hints: `->hasMany()` returns `\Illuminate\Database\Eloquent\Relations\HasMany`
- Task parent/child: `Task->subTasks()` and `Task->parentTask()`
- Tasks link to: `Customer`, `User` (assignedTech), `OriginalTech`, `TaskDetail`, `TaskMedia[]`, `InventoryTransaction[]`

### Casts & Type Safety
All enums are backed enums: `'task_type' => TaskType::class`
Decimals: `'company_price' => 'decimal:2'`
Dates: `'scheduled_date' => 'date'`, `'completion_date' => 'datetime'`

### Scopes
Define reusable query scopes: `Task::scopeBillable(Builder $query)` enables `Task::billable()->get()`

## External Integrations
- **Wire3**: External ISP system referenced by `wire3_cid` (Customer), `wire3_email` (User), `saf_link` (Task)
- **PDF Generation**: Uses `barryvdh/laravel-dompdf` for invoice/report PDFs
- **Location Tracking**: `LiveTechLocationWidget` (Livewire) displays real-time tech GPS via `current_lat`/`current_lng` on User model

## Important "Why" Decisions

### Why Auto-Generate DropBury Sub-Tasks?
NewInstall work billed at $350 but may require follow-up burial. When tech completes install without finishing bury (`drop_bury_status=false`), system creates `NotBillable` DropBury sub-task for dispatcher to re-assign. Prevents double-billing customer while tracking tech costs.

### Why TaskObserver Uses `isDirty()` Checks?
Prevents overwriting manual price edits in Filament forms. If user explicitly sets price, observer skips auto-fill even if value is null initially.

### Why Week-Based Payroll?
Aligns with field service industry standard (Sunday-Saturday pay periods). ISO week calculations ensure consistent period boundaries for recurring loan deductions.

## Files to Reference
- Task logic: [Task.php](../app/Models/Task.php), [TaskObserver.php](../app/Observers/TaskObserver.php)
- Filament resources: [app/Filament/Resources/](../app/Filament/Resources/)
- Enums: [app/Enums/](../app/Enums/)
- Config: [composer.json](../composer.json) (custom scripts), [vite.config.js](../vite.config.js)
