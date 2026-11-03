<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\City;
use App\Models\Employee;
use App\Models\State;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $state = State::firstOrCreate(
            ['state_code' => 'RJ'],
            ['state_name' => 'Rajasthan', 'country' => 'India']
        );

        $city = City::firstOrCreate(
            ['city_name' => 'Kotputli'],
            ['city_code' => 'KTP', 'state_id' => $state->id]
        );

        $branch = Branch::firstOrCreate(
            ['branch_code' => 'KTP01'],
            [
                'branch_name' => 'Kotputali',
                'branch_slug' => 'kotputali',
                'city_id' => $city->id,
                'address' => '2nd Floor, Shree Shyam Tower, Near Nainital Bank, Kotputli, Jaipur-303108.',
                'pin_code' => '303108',
                'meta' => [
                    'branch_in_charge_name' => 'Vikash Kumar Githala',
                    'branch_in_charge_employee_code' => '4749',
                    'cm_asm_name' => 'Leela Dhar Gurjar',
                    'cm_asm_code' => '4341',
                ],
            ]
        );

        // Saket Branch from Live Server
        $delhiState = State::firstOrCreate(
            ['state_code' => 'DL'],
            ['state_name' => 'Delhi', 'country' => 'India']
        );

        $delhiCity = City::firstOrCreate(
            ['city_name' => 'New Delhi'],
            ['city_code' => 'ND', 'state_id' => $delhiState->id]
        );

        $saketBranch = Branch::firstOrCreate(
            ['branch_code' => 'SK'],
            [
                'branch_name' => 'Saket',
                'branch_slug' => 'saket',
                'city_id' => $delhiCity->id,
                'address' => 'Saket Phase 1',
                'pin_code' => '110018',
                'meta' => [
                    'branch_in_charge_name' => 'Aman Rawat',
                    'branch_in_charge_phone_number' => '9871218483',
                    'branch_in_charge_address' => 'SAKET J-Block',
                    'branch_in_charge_email' => 'amanrawat0506@gmail.com',
                    'branch_in_charge_employee_code' => 'EMP01',
                    'cm_asm_name' => 'Manish',
                    'cm_asm_mobile_number' => '9871218482',
                    'cm_asm_email' => 'manish05@gmail.com',
                    'cm_asm_code' => 'EMP012',
                ],
            ]
        );

        // Employees from user data
        $employees = [
            [
                'employee_code' => '40321',
                'employee_first_name' => 'Manish',
                'employee_last_name' => 'Kumar',
                'designation' => 'Admin',
                'date_of_joining' => '2024-01-01',
                'mobile_number' => '7503196856',
                'email' => 'manish.mishra@lifc.in',
                'department' => 'IT',
                'branch_id' => $saketBranch->id,
                'remarks' => 'In Office',
            ],
            [
                'employee_code' => '1831',
                'employee_first_name' => 'Sher Singh',
                'employee_last_name' => 'Choudhary',
                'designation' => 'Senior Executive',
                'date_of_joining' => '2021-08-11',
                'mobile_number' => '9057258070',
                'department' => 'Collection',
                'branch_id' => $branch->id,
                'remarks' => 'In Field',
            ],
            [
                'employee_code' => '2757',
                'employee_first_name' => 'Jitendra',
                'employee_last_name' => 'Sharma',
                'designation' => 'Executive',
                'date_of_joining' => '2022-10-04',
                'mobile_number' => '9784493249',
                'department' => 'Collection',
                'branch_id' => $branch->id,
                'remarks' => 'In Field',
            ],
            [
                'employee_code' => '4749',
                'employee_first_name' => 'Vikash Kumar',
                'employee_last_name' => 'Githala',
                'designation' => 'Branch Manager',
                'date_of_joining' => '2024-09-06',
                'mobile_number' => '6376922351',
                'department' => 'SALES',
                'branch_id' => $branch->id,
                'remarks' => 'In Office',
            ],
            [
                'employee_code' => '4988',
                'employee_first_name' => 'Ajay Kumar',
                'employee_last_name' => 'Jat',
                'designation' => 'Senior Executive',
                'date_of_joining' => '2024-11-08',
                'mobile_number' => '8741989697',
                'department' => 'SALES',
                'branch_id' => $branch->id,
                'remarks' => 'In Field',
            ],
            [
                'employee_code' => '6242',
                'employee_first_name' => 'Sandeep Kumar',
                'employee_last_name' => 'Yadav',
                'designation' => 'Executive',
                'date_of_joining' => '2025-08-12',
                'mobile_number' => '9352858389',
                'department' => 'SALES',
                'branch_id' => $branch->id,
                'remarks' => 'In Office',
            ],
            [
                'employee_code' => '6452',
                'employee_first_name' => 'Raghuveer Singh',
                'employee_last_name' => 'Gurjar',
                'designation' => 'Executive',
                'date_of_joining' => '2025-09-13',
                'mobile_number' => '7665373024',
                'department' => 'SALES',
                'branch_id' => $branch->id,
                'remarks' => 'Not coming office from 10-05-2026',
            ],
            [
                'employee_code' => '6555',
                'employee_first_name' => 'Anil Kumar',
                'employee_last_name' => 'Yadav',
                'designation' => 'Assistant Manager',
                'date_of_joining' => '2025-10-03',
                'mobile_number' => '8947945199',
                'department' => 'Credit',
                'branch_id' => $branch->id,
                'remarks' => 'In Office',
            ],
            [
                'employee_code' => '6580',
                'employee_first_name' => 'Rambeer',
                'employee_last_name' => 'Yadav',
                'designation' => 'Executive',
                'date_of_joining' => '2025-10-09',
                'mobile_number' => '9602446928',
                'department' => 'SALES',
                'branch_id' => $branch->id,
                'remarks' => 'In Office',
            ],
            [
                'employee_code' => '8063',
                'employee_first_name' => 'Surendra Singh',
                'employee_last_name' => 'Rathore',
                'designation' => 'Disbursement Officer',
                'date_of_joining' => '2026-01-12',
                'mobile_number' => '8696321904',
                'department' => 'OPERATIONS',
                'branch_id' => $branch->id,
                'remarks' => 'In Office',
            ],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(['employee_code' => $emp['employee_code']], $emp);
        }
    }
}
