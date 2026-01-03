<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FileInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $fileInfoData = [
            [
                'lead_id'    => 1,
                'file_sl_no' => 'CA-2026-01-000001',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 2,
                'file_sl_no' => 'CA-2026-01-000002',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 3,
                'file_sl_no' => 'CA-2026-01-000003',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),

            ],
            [
                'lead_id'    => 4,
                'file_sl_no' => 'CA-2026-01-000004',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 5,
                'file_sl_no' => 'CA-2026-01-000005',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 6,
                'file_sl_no' => 'CA-2026-01-000006',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 7,
                'file_sl_no' => 'CA-2026-01-000007',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 8,
                'file_sl_no' => 'CA-2026-01-000008',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 9,
                'file_sl_no' => 'CA-2026-01-000009',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'lead_id'    => 10,
                'file_sl_no' => 'CA-2026-01-000010',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];

        foreach ($fileInfoData as $index => $file) {

            $fileInfoId = DB::table('file_info')->insertGetId([
                'lead_id'    => $file['lead_id'],
                'file_sl_no' => $file['file_sl_no'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Applicant
            DB::table('candidate_info')->insert([
                'lead_id'        => $file['lead_id'],
                'file_info_id'   => $fileInfoId,
                'file_sl_no'     => $file['file_sl_no'],
                'candidate_type' => 'Applicant',
                'candidate_name' => 'Applicant ' . ($index + 1),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);

            // Supplementary
            DB::table('candidate_info')->insert([
                'lead_id'        => $file['lead_id'],
                'file_info_id'   => $fileInfoId,
                'file_sl_no'     => $file['file_sl_no'],
                'candidate_type' => 'Supplementary',
                'candidate_name' => 'Supplementary ' . ($index + 1),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);
        }
    }
}
