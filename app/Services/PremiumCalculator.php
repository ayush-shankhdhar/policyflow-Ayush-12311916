<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class PremiumCalculator
{
    /**
     * Calculate the premium based on business rules.
     *
     * @param  array  $data  Must contain keys: plan_code, trip_start, trip_end, date_of_birth, destination, sum_insured
     * @return string  Decimal string with 2 places.
     */
    public function calculate(array $data): string
    {
        // Base daily rates (in rupees)
        $baseRates = [
            'BASIC' => '12',
            'PLUS' => '20',
            'PREMIUM' => '30',
        ];

        // Age multiplier at trip start
        $age = $this->calculateAge($data['date_of_birth'], $data['trip_start']);
        if ($age <= 17) {
            $ageMultiplier = '1.10';
        } elseif ($age <= 60) {
            $ageMultiplier = '1.00';
        } elseif ($age <= 70) {
            $ageMultiplier = '1.50';
        } else {
            $ageMultiplier = '2.00';
        }

        // Destination multiplier
        $destMultiplier = $data['destination'] === 'INTERNATIONAL' ? '1.80' : '1.00';

        // Coverage multiplier
        $coverageMap = [
            100000 => '1.00',
            300000 => '1.25',
            500000 => '1.50',
        ];
        $coverageMultiplier = $coverageMap[$data['sum_insured']];

        // Inclusive travel days
        $tripStart = Carbon::parse($data['trip_start']);
        $tripEnd = Carbon::parse($data['trip_end']);
        $travelDays = $tripStart->diffInDays($tripEnd) + 1; // inclusive

        // Formula using bc math for decimal safety
        $premium = bcmul($baseRates[$data['plan_code']], (string) $travelDays, 4);
        $premium = bcmul($premium, $ageMultiplier, 4);
        $premium = bcmul($premium, $destMultiplier, 4);
        $premium = bcmul($premium, $coverageMultiplier, 4);
        // Round to 2 decimal places
        return number_format((float) $premium, 2, '.', '');
    }

    /**
     * Calculate age at a given date.
     */
    protected function calculateAge(string $dob, string $atDate): int
    {
        return Carbon::parse($dob)->diffInYears(Carbon::parse($atDate));
    }
}
?>
