<?php

namespace App\Http\Exports\Sheets;

use App\Http\Exports\Concerns\ReferenceSheetStyle;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomersReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle
{
    use ReferenceSheetStyle;

    public function query()
    {
        return Customer::query()
            ->with([
                'account',
                'accType',
                'class',
                'specialty',
            ])
            ->whereHas('account')
            ->orderBy('customers.account_id');
    }

    public function headings(): array
    {
        return [
            'CODE',
            'Account Name',
            'Account Type',
            'Customer Name',
            'Specialty',
            'Class',
            'Phone',
            'Phone 1',
            'Brief',
            'Work Days',
            'Work Time From',
            'Work Time To',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->Uuid,

            optional($customer->account)->name,

            optional($customer->accType)->name,

            $customer->name,

            optional($customer->specialty)->name ?? '',

            optional($customer->class)->name ?? '',

            $customer->phone ?? '',

            $customer->phone_1 ?? '',

            $customer->brief ?? '',

            $this->formatWorkDays($customer->work_days),

            $customer->work_start_time ?? '',

            $customer->work_end_time ?? '',
        ];
    }

    /**
     * Convert work_days IDs to day names.
     *
     * Example:
     * [1, 3, 7]
     *
     * becomes:
     * SAT, MON, FRI
     */
    protected function formatWorkDays($workDays): string
    {
        if (empty($workDays)) {
            return '';
        }

        if (is_string($workDays)) {
            $decoded = json_decode($workDays, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $workDays = $decoded;
            } else {
                $workDays = explode(',', $workDays);
            }
        }

        if (!is_array($workDays)) {
            return '';
        }

        $days = [
            1 => 'SAT',
            2 => 'SUN',
            3 => 'MON',
            4 => 'TUES',
            5 => 'WEND',
            6 => 'THUR',
            7 => 'FRI',
        ];

        return collect($workDays)
            ->map(function ($day) use ($days) {
                /*
                 * In case the value is:
                 * { "id": 1 }
                 */
                if (is_array($day)) {
                    $day = $day['id'] ?? null;
                }

                return $days[(int) $day] ?? null;
            })
            ->filter()
            ->implode(', ');
    }

    public function title(): string
    {
        return 'Customers';
    }

    protected function columns(): array
    {
        return [
            'A', // CODE
            'B', // Account Name
            'C', // Account Type
            'D', // Customer Name
            'E', // Specialty
            'F', // Class
            'G', // Phone
            'H', // Phone 1
            'I', // Brief
            'J', // Work Days
            'K', // Work Time From
            'L', // Work Time To
        ];
    }
}