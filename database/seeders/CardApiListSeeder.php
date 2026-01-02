<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CardApiListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('card_api_list')->insert([
            [
                'api_name'             => 'Card Customer Dupe Check',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestware/getCardCustomerDupeCheck',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 1,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'createClientCreditCard',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/createClientCreditCard',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 2,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'setCreditCardContractLimit',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/setCreditCardContractLimit',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 3,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'updateCreditCardCustomer',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/updateCreditCardCustomer',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 4,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'updateCreditCardContract',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/updateCreditCardContract',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 5,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'updateCreditCard',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/updateCreditCard',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 6,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'addCardMemo',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestware/addCardMemo',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 7,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'addCardMobileNumber',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestware/v2/addCardMobileNumber',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 8,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'addCardEmailAdress',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestware/v2/addCardEmailAdress',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 9,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'createCreditCardCustomer',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/createCreditCardCustomer',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 10,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'api_name'             => 'supplymentaryCardCreation',
                'api_code'             => 'NTOB_API',
                'api_url'              => 'https://apim-uat01.thecitybank.com/cblrestservices/supplymentaryCardCreation',
                'api_type'             => 'NTOB',
                'is_active'            => 1,
                'api_calling_sequence' => 11,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
        ]);
    }
}
