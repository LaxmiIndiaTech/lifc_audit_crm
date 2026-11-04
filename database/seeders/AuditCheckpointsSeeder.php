<?php

namespace Database\Seeders;

use App\Models\AuditCheckpoint;
use Illuminate\Database\Seeder;

class AuditCheckpointsSeeder extends Seeder
{
    public function run(): void
    {
        $checkpoints = [
            // Section A: Cash and Cheque Management Norms (Carry: 4)
            [
                'section' => 'A',
                'section_name' => 'Cash and Cheque Management Norms',
                'checkpoint_code' => 'A.1',
                'scope_point' => 'Collection to be deposited in T+1, if any exception to be intimated HO team',
                'carry_marks' => 2.0000,
                'weight' => 0.5000,
                'default_department' => 'CMS',
                'is_auto_calculated' => true,
                'sort_order' => 1,
            ],
            [
                'section' => 'A',
                'section_name' => 'Cash and Cheque Management Norms',
                'checkpoint_code' => 'A.2',
                'scope_point' => 'Collection Amount Deposited in Personal Account',
                'carry_marks' => 2.0000,
                'weight' => 0.5000,
                'default_department' => 'CMS',
                'is_auto_calculated' => true,
                'sort_order' => 2,
            ],

            // Section B: Operational Aspects and Application Awareness (Carry: 6)
            [
                'section' => 'B',
                'section_name' => 'Operational Aspects and Application Awareness',
                'checkpoint_code' => 'B.1',
                'scope_point' => 'Interaction with Branch regarding Software Uses Knowledge about: LOS/LMS, CLU, AIMS, HRMS, CRM, MS-Office, Visitor App',
                'carry_marks' => 3.0000,
                'weight' => 0.5000,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 3,
            ],
            [
                'section' => 'B',
                'section_name' => 'Operational Aspects and Application Awareness',
                'checkpoint_code' => 'B.2',
                'scope_point' => 'Branch have updated product/Credit policy in Physical for Readable for every Employee.',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 4,
            ],
            [
                'section' => 'B',
                'section_name' => 'Operational Aspects and Application Awareness',
                'checkpoint_code' => 'B.3',
                'scope_point' => 'Whether the information regarding non-refundable charges has been pasted on branch.',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 5,
            ],
            [
                'section' => 'B',
                'section_name' => 'Operational Aspects and Application Awareness',
                'checkpoint_code' => 'B.4',
                'scope_point' => 'Check whether all employee (New & old employees) are wearing ID card. Mentioned Employee Code with name of employees. If any',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 6,
            ],

            // Section C: Customer Management (Carry: 5)
            [
                'section' => 'C',
                'section_name' => 'Customer Management',
                'checkpoint_code' => 'C.1',
                'scope_point' => 'Information about upfront charges which is deducted from Disbursement has been given to Customer.',
                'carry_marks' => 2.0000,
                'weight' => 0.4000,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 7,
            ],
            [
                'section' => 'C',
                'section_name' => 'Customer Management',
                'checkpoint_code' => 'C.2',
                'scope_point' => 'Customer Complaint Resolutions',
                'carry_marks' => 1.0000,
                'weight' => 0.2000,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 8,
            ],
            [
                'section' => 'C',
                'section_name' => 'Customer Management',
                'checkpoint_code' => 'C.3',
                'scope_point' => 'Property Papers and NOC Acknowledgement copy sent to HO',
                'carry_marks' => 2.0000,
                'weight' => 0.4000,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 9,
            ],

            // Section D: Display and Regulatory requirements (Carry: 10)
            [
                'section' => 'D',
                'section_name' => 'Display and Regulatory requirements',
                'checkpoint_code' => 'D.1',
                'scope_point' => 'All mandatory docs displayed on Notice Board: RBI license, Grievance Details, GST Certificate, Schedule of Charges, Shop Act/GUMASTA, Emergency Services, ESI PF, Certificate of Incorporation, Gratuity/Minimum Wage, Fair Practice Code, POSH',
                'carry_marks' => 7.0000,
                'weight' => 0.7000,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 10,
            ],
            [
                'section' => 'D',
                'section_name' => 'Display and Regulatory requirements',
                'checkpoint_code' => 'D.2',
                'scope_point' => 'Company/Branch/HO Address, Working Days & Hours, Weekly Off, Toll Free No., Customer Care Mail, GSTIN & CIN at branch entrance. Name board visible when closed; LIFC board outside not faded.',
                'carry_marks' => 2.0000,
                'weight' => 0.2000,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 11,
            ],
            [
                'section' => 'D',
                'section_name' => 'Display and Regulatory requirements',
                'checkpoint_code' => 'D.3',
                'scope_point' => 'Direction Sign Board displayed at appropriate locations in case of branch having indirect approach for visitor. (Wherever applicable)',
                'carry_marks' => 1.0000,
                'weight' => 0.1000,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 12,
            ],

            // Section E: Document Management (Carry: 4)
            [
                'section' => 'E',
                'section_name' => 'Document Management',
                'checkpoint_code' => 'E.1',
                'scope_point' => 'Ensure Branch maintains and updates: Courier Register, Customer Grievance Register, NOC Paper Handover Register.',
                'carry_marks' => 1.0000,
                'weight' => 0.2500,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 13,
            ],
            [
                'section' => 'E',
                'section_name' => 'Document Management',
                'checkpoint_code' => 'E.2',
                'scope_point' => 'OPP and other critical documents should be placed properly in branch',
                'carry_marks' => 1.0000,
                'weight' => 0.2500,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 14,
            ],
            [
                'section' => 'E',
                'section_name' => 'Document Management',
                'checkpoint_code' => 'E.3',
                'scope_point' => 'Is there any Post Dated Cheques (PDC) taken without the name of Company.',
                'carry_marks' => 1.0000,
                'weight' => 0.2500,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 15,
            ],
            [
                'section' => 'E',
                'section_name' => 'Document Management',
                'checkpoint_code' => 'E.4',
                'scope_point' => 'Is there any Old files (reject and cancel) and Unauthorised Stamp Lying with Branches.',
                'carry_marks' => 1.0000,
                'weight' => 0.2500,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 16,
            ],

            // Section F: Admin & House Keeping (Carry: 6)
            [
                'section' => 'F',
                'section_name' => 'Admin & House Keeping',
                'checkpoint_code' => 'F.1',
                'scope_point' => 'Ensure latest copy of rent/lease agreement between LIFC and landlord is readily available at branch with contact details of Landlord.',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 17,
            ],
            [
                'section' => 'F',
                'section_name' => 'Admin & House Keeping',
                'checkpoint_code' => 'F.2',
                'scope_point' => 'Whether Branch employee found with Tobacco, Cigarette, Chewing etc. If any, mentioned Emp Code with Name.',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 18,
            ],
            [
                'section' => 'F',
                'section_name' => 'Admin & House Keeping',
                'checkpoint_code' => 'F.3',
                'scope_point' => 'Ensure branch is fully maintained and clean; check in petty Cash how much is being paid for house keeping.',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 19,
            ],
            [
                'section' => 'F',
                'section_name' => 'Admin & House Keeping',
                'checkpoint_code' => 'F.4',
                'scope_point' => 'Major Admin assets to be matched with HO Records like Chair, movable furniture etc.',
                'carry_marks' => 2.0000,
                'weight' => 0.3333,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 20,
            ],
            [
                'section' => 'F',
                'section_name' => 'Admin & House Keeping',
                'checkpoint_code' => 'F.5',
                'scope_point' => 'Scrap Assets details need to be maintained in AIMS as per the policy',
                'carry_marks' => 1.0000,
                'weight' => 0.1666,
                'default_department' => 'Admin',
                'is_auto_calculated' => false,
                'sort_order' => 21,
            ],

            // Section G: Information Security (Carry: 5)
            [
                'section' => 'G',
                'section_name' => 'Information Security',
                'checkpoint_code' => 'G.1',
                'scope_point' => 'Ensure all USB Ports (PAN Drive & USB cable etc.) are blocked; only copy from pen drive to system, not vice versa.',
                'carry_marks' => 1.0000,
                'weight' => 0.2000,
                'default_department' => 'IT',
                'is_auto_calculated' => false,
                'sort_order' => 22,
            ],
            [
                'section' => 'G',
                'section_name' => 'Information Security',
                'checkpoint_code' => 'G.2',
                'scope_point' => 'Computer/Laptop need to be checked for unwanted/unethical data/movie/pics',
                'carry_marks' => 1.0000,
                'weight' => 0.2000,
                'default_department' => 'IT',
                'is_auto_calculated' => false,
                'sort_order' => 23,
            ],
            [
                'section' => 'G',
                'section_name' => 'Information Security',
                'checkpoint_code' => 'G.3',
                'scope_point' => 'Ensure Anti-virus is installed & updated on all computer systems at branch.',
                'carry_marks' => 1.0000,
                'weight' => 0.2000,
                'default_department' => 'IT',
                'is_auto_calculated' => false,
                'sort_order' => 24,
            ],
            [
                'section' => 'G',
                'section_name' => 'Information Security',
                'checkpoint_code' => 'G.4',
                'scope_point' => 'IT Assets to be matched with HO Records/AIMS.',
                'carry_marks' => 2.0000,
                'weight' => 0.4000,
                'default_department' => 'IT',
                'is_auto_calculated' => false,
                'sort_order' => 25,
            ],

            // Section H: TAT Login to Disbursement (Carry: 5)
            [
                'section' => 'H',
                'section_name' => 'TAT Login to Disbursement (Average Of Last Quarter)',
                'checkpoint_code' => 'H.1',
                'scope_point' => "Average Days:\n1. 0-10 Days (100% Marks)\n2. 11-20 Days (75% Marks)\n3. 21-30 Days (50% Marks)\n4. 31 and above (20% Marks)",
                'carry_marks' => 5.0000,
                'weight' => 1.0000,
                'default_department' => 'Ops',
                'is_auto_calculated' => false,
                'sort_order' => 26,
            ],

            // Section I: Target Vs Achievement (Carry: 15)
            [
                'section' => 'I',
                'section_name' => 'Target Vs Achievement (Average Of Last Quarter)',
                'checkpoint_code' => 'I.1',
                'scope_point' => "Achievement Average:\n1. > 90% (100% Marks)\n2. 60% above and up to 90% (75% Marks)\n3. 40% above and up to 60% (50% Marks)\n4. Below 40% (20% Marks)",
                'carry_marks' => 15.0000,
                'weight' => 1.0000,
                'default_department' => 'Sales',
                'is_auto_calculated' => false,
                'sort_order' => 27,
            ],

            // Section J: Branch NPA % (Carry: 15)
            [
                'section' => 'J',
                'section_name' => 'Branch NPA % (Average Of Last Quarter)',
                'checkpoint_code' => 'J.1',
                'scope_point' => "Branch NPA % Risk:\n1. <= 2% Low Risk (100% Marks)\n2. > 2% to <= 5% Medium Risk (50% Marks)\n3. > 5% to <= 8% High Risk (20% Marks)\n4. > 8% Very High Risk (0% Marks)",
                'carry_marks' => 15.0000,
                'weight' => 1.0000,
                'default_department' => 'Collection',
                'is_auto_calculated' => false,
                'sort_order' => 28,
            ],

            // Section K: Manpower Attrition (Carry: 10)
            [
                'section' => 'K',
                'section_name' => 'Manpower Attrition (Average Of Last Quarter)',
                'checkpoint_code' => 'K.1',
                'scope_point' => "Manpower Attrition (Average Of Last Quarter):\n1. < 5% (100% Marks)\n2. 5% to < 10% (50% Marks)\n3. >= 10% (0% Marks)",
                'carry_marks' => 10.0000,
                'weight' => 1.0000,
                'default_department' => 'HR',
                'is_auto_calculated' => false,
                'sort_order' => 29,
            ],

            // Section L: Application Utilization (Carry: 5)
            [
                'section' => 'L',
                'section_name' => 'Application Utilization (AIMS & Visitor App)',
                'checkpoint_code' => 'L.1',
                'scope_point' => "Application Utilization (AIMS & Visitor App):\n1. > 80% (100% Marks)\n2. 60% to 80% (75% Marks)\n3. 40% to 60% (50% Marks)\n4. 20% to 40% (25% Marks)\n5. <= 20% (0% Marks)",
                'carry_marks' => 5.0000,
                'weight' => 1.0000,
                'default_department' => 'IT',
                'is_auto_calculated' => false,
                'sort_order' => 30,
            ],

            // Section M: Loan File Query Resolution (Carry: 10)
            [
                'section' => 'M',
                'section_name' => 'Loan File Query Resolution (Average Of Last Quarter)',
                'checkpoint_code' => 'M.1',
                'scope_point' => "Loan File Query Resolution (Average of the Last Quarter):\n1. > 80% (100% Marks)\n2. 60% to 80% (75% Marks)\n3. 40% to 60% (50% Marks)\n4. <= 40% (20% Marks)",
                'carry_marks' => 10.0000,
                'weight' => 1.0000,
                'default_department' => 'Credit',
                'is_auto_calculated' => false,
                'sort_order' => 31,
            ],
        ];

        foreach ($checkpoints as $cp) {
            AuditCheckpoint::updateOrCreate(
                ['checkpoint_code' => $cp['checkpoint_code']],
                $cp
            );
        }
    }
}
