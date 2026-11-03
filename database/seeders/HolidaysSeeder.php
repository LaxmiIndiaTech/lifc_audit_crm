<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaysSeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['holiday_date' => '2026-01-26', 'holiday_name' => 'Republic Day', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-03-04', 'holiday_name' => 'Holi (Dhulandi)', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-03-20', 'holiday_name' => 'Id-ul-Fitr (Eid)', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-04-03', 'holiday_name' => 'Good Friday', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-04-14', 'holiday_name' => 'Dr. B.R. Ambedkar Jayanti', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-04-17', 'holiday_name' => 'Mahavir Jayanti', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-05-01', 'holiday_name' => 'May Day / Bank Holiday', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-05-27', 'holiday_name' => 'Bakrid / Eid-ul-Adha', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-08-15', 'holiday_name' => 'Independence Day', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-08-27', 'holiday_name' => 'Raksha Bandhan', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-09-04', 'holiday_name' => 'Janmashtami', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-10-02', 'holiday_name' => 'Mahatma Gandhi Jayanti', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-10-20', 'holiday_name' => 'Dussehra (Vijay Dashami)', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-11-08', 'holiday_name' => 'Diwali (Deepavali)', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-11-09', 'holiday_name' => 'Govardhan Puja', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-11-10', 'holiday_name' => 'Bhai Dooj', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-11-24', 'holiday_name' => 'Guru Nanak Jayanti', 'type' => 'bank_holiday'],
            ['holiday_date' => '2026-12-25', 'holiday_name' => 'Christmas Day', 'type' => 'bank_holiday'],
        ];

        foreach ($holidays as $h) {
            Holiday::firstOrCreate(
                ['holiday_date' => $h['holiday_date'], 'state_id' => null],
                [
                    'holiday_name' => $h['holiday_name'],
                    'type' => $h['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
