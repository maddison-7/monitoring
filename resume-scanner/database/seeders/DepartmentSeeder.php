<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Accounting and Auditing', 'code' => 'ACC_AUD'],
            ['name' => 'ACSE', 'code' => 'ACSE'],
            ['name' => 'Agricultural and Natural Resources', 'code' => 'AGR_NR'],
            ['name' => 'Banking, Economics and Financial Services', 'code' => 'BANK_ECO_FIN'],
            ['name' => 'Climate Change', 'code' => 'CLIMATE'],
            ['name' => 'Creative and Design', 'code' => 'CREATIVE_DESIGN'],
            ['name' => 'CSE', 'code' => 'CSE'],
            ['name' => "Driver's", 'code' => 'DRIVERS'],
            ['name' => 'Education and Training', 'code' => 'EDU'],
            ['name' => 'Engineering and Construction', 'code' => 'ENG_CONST'],
            ['name' => 'Environmental Sciences and Geography', 'code' => 'ENV_GEO'],
            ['name' => 'Farming and Livestock', 'code' => 'FARM_LIVE'],
            ['name' => 'Healthcare and Pharmaceutical', 'code' => 'HEALTH_PHARMA'],
            ['name' => 'HR & Administration', 'code' => 'HR_ADMIN'],
            ['name' => 'International Relations', 'code' => 'INT_REL'],
            ['name' => 'IT and Telecoms', 'code' => 'IT_TELECOM'],
            ['name' => 'Land Management', 'code' => 'LAND_MGMT'],
            ['name' => 'Legal', 'code' => 'LEGAL'],
            ['name' => 'Linguistics', 'code' => 'LINGUISTICS'],
            ['name' => 'Manufacturing', 'code' => 'MANUFACTURING'],
            ['name' => 'Marketing,Media and Brand', 'code' => 'MKT_MEDIA_BRAND'],
            ['name' => 'Physical & Natural Sciences', 'code' => 'PHYS_NAT_SCI'],
            ['name' => 'Procurement & Logistic Management', 'code' => 'PROC_LOG_MGMT'],
            ['name' => 'Project, Planning and Policy Management', 'code' => 'PROJ_PLAN_POLICY'],
            ['name' => 'Religious Studies', 'code' => 'REL_STUDIES'],
            ['name' => 'Research,Science and Biotech', 'code' => 'RES_SCI_BIO'],
            ['name' => 'Security', 'code' => 'SECURITY'],
            ['name' => 'Sociology, Political Science, Community and Social Development', 'code' => 'SOC_POL_COMM'],
            ['name' => 'Statistics and Mathematics', 'code' => 'STATS_MATH'],
            ['name' => 'Taxation and Social Protection', 'code' => 'TAX_SOC_PROT'],
            ['name' => 'Tourism and Travel', 'code' => 'TOURISM_TRAVEL'],
            ['name' => 'Trades and Services', 'code' => 'TRADES_SERV'],
            ['name' => 'Transport and Logistics', 'code' => 'TRANSPORT_LOG'],
            ['name' => 'Water, Mining and Natural Resources', 'code' => 'WATER_MINING_NR'],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                $department,
            );
        }
    }
}
