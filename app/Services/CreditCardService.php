<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreditCardService
{


    public static function prepareCardAPIData($file_info_id)
    {
        $data = [
            'applicant' => (object)[
                'candidate_info_id' => 1,
                'lead_id' => 1,
                'file_info_id' => 1001,
                'file_sl_no' => 'CA-000000001',
                'candidate_type' => 'Applicant',
                'client_id' => 'CLIENT001',
                // 'customer_id_cms' => 'CMS123456',
                'customer_id_cms' => '',
                'contract_no' => 'CN-987654',
                'approved_amount' => '500000',
                'card_facility_type' => 'New Card',
                'existing_file_sl_no_calc' => 'CA-000000001',
                'candidate_title' => 1,
                'candidate_name' => 'Millat Hussain',
                'name_transliteration' => 'Millat Hussain',
                'gender' => 'M',
                'dob' => '15/08/1995',
                'entry_date' => '01/12/2025',
                'approve_date' => '05/12/2025',
                'gift_entry_date' => '06/12/2025',
                'father_name' => 'Abdul Hussain',
                'mother_name' => 'Ayesha Begum',
                'spouse_name' => '',
                'place_of_birth' => 'Dhaka',
                'residential_status' => '1',
                'education_level' => 'GRADUATE',
                'marital_status' => 'SINGLE',
                'nationality' => '1',
                'staff_id' => '',
                'company_id' => 'COMP001',
                'company_name' => 'Tech Soft Ltd',
                'department_name' => 'IT',
                'designation_name' => 'Software Engineer',
                'etin' => '123456789',
                'nid' => '19951234567890123',
                'smart_card' => 'SC12345678',
                'passport_no' => 'A01234567',
                'name_on_passport' => 'MILLAT HUSSAIN',
                'address_on_passport' => 'Dhaka, Bangladesh',
                'email' => 'millat@test.com',
                'mobile_no' => '01711111111',
                'phone_no' => '',
                'fax_no' => '',
                'tax_return_assessment_year' => '2024',
                'passport_issuing_date' => '01/01/2020',
                'passport_expiry_date' => '01/01/2030',
                'passport_issue_place' => 'Dhaka',
                'passport_issuing_country_name' => 'Bangladesh',
                'source_user_id' => 'ADMIN',
                'document_issue_country_id' => 'BD',
                'ccs_customer_type_id' => '1',
                'receive_card_through' => 'COURIER',
                'delivery_address_type_name' => 'PRESENT',
                'br_id_to_receive_card' => 'BR001',

                // Address (ADR)
                'residence_country_code' => 'BD',
                'residence_region_code' => 'DHK',
                'residence_city_code' => 'DHAKA',
                'residence_address' => 'Mirpur, Dhaka',
                'residence_postal_code' => '1216',

                // Reference (RF)
                'reference_name' => 'Rahim Uddin',
                'reference_address' => 'Uttara, Dhaka',
                'reference_phone_number' => '01811111111',
                'second_reference_name' => 'Karim Uddin',
                'second_reference_address' => 'Banani, Dhaka',
                'second_reference_phone_number' => '01911111111',

                'product_id' => 'CARD001',
                'product_generic_id' => 'VISA',
                'occupation_status_id' => '1',
                'application_source_id' => 'ONLINE',
                'third_party_agent' => '',
                'auto_debit_eftn_instruction' => 'YES',
                'city_shield' => 'NO',
                'security_lien_info' => '',
                'collateral_value_local' => '',
                'collateral_value_foreign' => '',
                'name_of_first_nominee' => 'Ayesha Begum',
                'first_nominee_percentage' => '100',
                'name_of_second_nominee' => '',
                'second_nominee_percentage' => '',
                'recommended_dbr' => '35',
                'approver_name' => 'Manager One',
                'corporate_field_mapping' => '',
                'liability_type' => 'INDIVIDUAL',
                'billing_payment_option' => 'MINIMUM',
                'receive_statement_through' => 'EMAIL',
                'casa_account_scheme' => 'SB',
                'welcome_gift' => 'YES',
                'campaign_id' => 'CMP2025',
                'required_cheque_book' => 'NO',
                'special_remarks' => '',
                'tz_product_id' => '',
                'card_fees_profile' => 'STANDARD',
                'is_le_card' => 'N',
                'customer_segment_id' => 'RETAIL',
                'occupation_code' => 'IT01',
                'business_name' => ''
            ],

            'supplementary' => [
                2 => (object)[
                    'id' => 2,
                    'candidate_type' => 'Supplementary',
                    'candidate_name' => 'Sara Hussain',
                    'gender' => 'F',
                    'dob' => '20/03/2000',
                    'mobile_no' => '01622222222',
                    'application_source_id' => 'ONLINE'
                ],

                3 => (object)[
                    'id' => 3,
                    'candidate_type' => 'Supplementary',
                    'candidate_name' => 'Arif Hussain',
                    'gender' => 'M',
                    'dob' => '10/11/1998',
                    'mobile_no' => '01833333333',
                    'application_source_id' => 'ONLINE'
                ],

                4 => (object)[
                    'id' => 4,
                    'candidate_type' => 'Supplementary',
                    'candidate_name' => 'Nusrat Jahan',
                    'gender' => 'F',
                    'dob' => '05/07/2002',
                    'mobile_no' => '01944444444',
                    'application_source_id' => 'ONLINE'
                ],

                5 => (object)[
                    'id' => 5,
                    'candidate_type' => 'Supplementary',
                    'candidate_name' => 'Tanvir Ahmed',
                    'gender' => 'M',
                    'dob' => '25/01/2001',
                    'mobile_no' => '01555555555',
                    'application_source_id' => 'ONLINE'
                ],
            ]
        ];
        return CommonService::convertToUpperCase($data);
    }

    public static function cleintIdByTin($data = []) // ETOB existing customer client ID by TIN API
    {
        $requestPayload = [
            "searchBy"             => "TIN",
            "searchValue"          => "270137473356",
            "channelTransactionId" => "LOS251217000091650718",
            "channelName"          => "ashiq",
            "channelSecret"        => "string"
        ];

        $response = array(
            'responseCode' => '100',
            'responseMessage' => 'Operation Successful.',
            'channelTransactionId' => '203424356tyKE3425466cESW',
            'serviceId' => '251950913018',
            'timeStamp' => '14-07-2025 09:13:26',
            'responseData' =>
            array(
                0 => array(
                    'clientId' => '980230',
                    'cardNo' => '371598XXXXX4531',
                    'customerName' => 'MD ASADUL HAQUE SARKER',
                    'cardStatus' => 'Declared',
                    'cardState' => 'Embossing',
                    'expiryDate' => '0329',
                ),
                1 => array(
                    'clientId' => '980230',
                    'cardNo' => '474867XXXXXX7791',
                    'customerName' => 'MD ASADUL HAQUE SARKER',
                    'cardStatus' => 'Open',
                    'cardState' => 'Given',
                    'expiryDate' => '0225',
                ),
            ),
        );


        CommonService::pushAPILogInQueue([
            'api_id'            => 1,
            'lead_id'           => $data['lead_id'],
            'file_info_id'      => $data['file_info_id'],
            'candidate_info_id' => $data['candidate_info_id'],
            'file_sl_no'        => $data['file_sl_no'],
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);

        return $response;
    }

    public static function _processE2B($file_info_id, $result)
    {
        $proceedThree = CreditCardService::beforeCallApi(3, $result['applicant']->lead_id, $result['applicant']->candidate_info_id, $file_info_id);
        $responseDataThree = null;
        if ($proceedThree) {
            $responseDataThree = json_decode($proceedThree->response, true);
        } else {
            $responseDataThree = self::create_credit_card_contract_with_card(
                $file_info_id,
                $result['applicant']->file_sl_no,
                $result['applicant']->tz_product_id,
                $result['applicant']->customer_id_cms,
                $result['applicant'],
            );
        }

        $customerId         = $result['applicant']->customer_id_cms ?? '';
        $customerContractNo = $responseDataThree['contractNumber'] ?? '';
        $customerCardNo     = $responseDataThree['cardNumber'] ?? '';
        $accountNumber      = $responseDataThree['accountNumber'] ?? '';

        if (!empty($customerContractNo) || !empty($customerCardNo)) {
            $proceedFour = CreditCardService::beforeCallApi(4, $result['applicant']->lead_id, $file_info_id, $result['applicant']->candidate_info_id);
            $responseDataFour = null;
            if ($proceedFour) {
                $responseDataFour = json_decode($proceedFour->response, true);
            } else {
                $responseDataFour = self::set_credit_card_contract_limit(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    $customerContractNo,
                    $customerCardNo,
                    $result['applicant']->approved_amount,
                    $result['applicant']
                );
            }
        }
    }

    public static function create_credit_card_contract_with_card($file_info_id, $file_sl_no, $contract_type_id, $customer_id_cms, $data)
    {

        $requestPayload = [
            "customerTitle"         => "1",
            "customerName"          => "MD RAKIBUL HASAN",
            "customerTransName"     => "MD RAKIBUL HASAN",
            "gender"                => "M",
            "dob"                   => "19830313000000",
            "documentMaskingBy"     => "2",
            "documentMaskingNumber" => "270137473356",
            "contractTypeId"        => "2256",
            "channelName"           => "ashiq",
            "channelSecret"         => "string",
            "channelTransactionId"  => "LOS251217387930738508"
        ];

        $response = [
            "contractNo"           => "22560000023644",
            "customerId"           => "2668128",
            "pan"                  => "376948122677475",
            "accountNumber"        => "1000500367404",
            "channelTransactionId" => "LOS251217387930738508",
            "serviceId"            => "ab951215-6bf9-4198-b072-cf1af8374765",
            "timestamp"            => "17-12-2025 15:06:14",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful."
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 2,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);


        return $response;
    }

    public static function set_credit_card_contract_limit($file_info_id, $file_sl_no, $contract_type_id, $customer_id_cms, $approved_amount, $data)
    {
        $requestPayload = [
            "contractId"           => "22560000023644",
            "cardNumber"           => "376948122677475",
            "amount"               => $approved_amount,
            "channelName"          => "ashiq",
            "channelSecret"        => "string",
            "channelTransactionId" => "LOS251217329168165206"
        ];

        $response = [
            "channelTransactionId" => "ashiq-LOS251217329168165206",
            "serviceId" => "c1214207-3a5e-4157-813a-4a9a5803daf6",
            "timestamp" => "17-12-2025 15:06:16",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful."
        ];


        CommonService::pushAPILogInQueue([
            'api_id'            => 4,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);

        return $response;
    }


    public static function beforeCallApi($apiId, $leadId, $fileInfoId, $candidateInfoId)
    {
        $preSuccessChck = DB::table('retry_api_queue')
            ->select('*')
            ->where([
                'api_id'            => $apiId,
                'lead_id'           => $leadId,
                'file_info_id'      => $fileInfoId,
                'candidate_info_id' => $candidateInfoId,
                'success_status'    => 1,
            ])
            ->first();

        return $preSuccessChck;
    }
}
