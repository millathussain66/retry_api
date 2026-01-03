<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
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
                'client_id' => '',
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
                    'candidate_info_id'     => 20,
                    'lead_id'               => 1,
                    'file_info_id'          => 1001,
                    'file_sl_no'            => 'CA-000000001',
                    'candidate_type'        => 'Supplementary',
                    'candidate_title'       => 'Supplementary',
                    'name_transliteration'  => 'name_transliteration',
                    'candidate_name'        => 'Sara Hussain',
                    'gender'                => 'F',
                    'dob'                   => '20/03/2000',
                    'mobile_no'             => '01622222222',
                    'application_source_id' => 'ONLINE',
                    'father_name'           => 'father_name',
                    'mother_name'           => 'mother_name',
                ],
                3 => (object)[
                    'candidate_info_id'     => 21,
                    'lead_id'               => 1,
                    'file_info_id'          => 1001,
                    'file_sl_no'            => 'CA-000000001',
                    'candidate_type'        => 'Supplementary',
                    'candidate_title'       => 'Supplementary',
                    'name_transliteration'  => 'name_transliteration',
                    'candidate_name'        => 'Sara Hussain',
                    'gender'                => 'F',
                    'dob'                   => '20/03/2000',
                    'mobile_no'             => '01622222222',
                    'application_source_id' => 'ONLINE',
                    'father_name'           => 'father_name',
                    'mother_name'           => 'mother_name',
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
                    'clientId' => '',
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
            'api_id'            => 3,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);

        return $response;
    }


    public static function _processN2B($file_info_id, $result)
    {

        // throw new Exceptions("API failed: Missing customer/card information");


        $proceedOne = CreditCardService::beforeCallApi(2, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);

        // beforeCallApi($apiId, $leadId, $fileInfoId, $candidateInfoId)

        $responseDataTwo = null;
        if ($proceedOne) {
            $responseDataTwo = json_decode($proceedOne->response, true);
        } else {
            $responseDataTwo = self::create_client_credit_card(
                $file_info_id,
                $result['applicant']->file_sl_no,
                $result['applicant']->candidate_title,
                $result['applicant']->candidate_name,
                $result['applicant']->name_transliteration,
                $result['applicant']->gender,
                $result['applicant']->dob,
                2,  // doc_masking_key. Fixed value: 2=TIN
                $result['applicant']->etin,
                $result['applicant']->tz_product_id,
                $result['applicant'],
            );
        }

        $customerId         = $responseDataTwo['customerId'] ?? '';
        $customerContractNo = $responseDataTwo['contractNo'] ?? '';
        $customerCardNo     = $responseDataTwo['pan'] ?? '';
        $accountNumber      = $responseDataTwo['accountNumber'] ?? '';

        if (empty($customerId) || empty($customerContractNo) || empty($customerCardNo)) {
            throw new Exceptions("API failed: Missing customer/card information");
        } else {

            $proceedThree = CreditCardService::beforeCallApi(3, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
            $responseDataThree = null;
            if ($proceedThree) {
                $responseDataThree = json_decode($proceedThree->response, true);
            } else {
                $responseDataThree = self::set_credit_card_contract_limit(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    $customerContractNo,
                    $customerCardNo,
                    $result['applicant']->approved_amount,
                    $result['applicant'],
                );
            }

            $proceedFour = CreditCardService::beforeCallApi(4, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
            $responseDataFour = null;
            if ($proceedFour) {
                $responseDataFour = json_decode($proceedFour->response, true);
            } else {
                $responseDataFour = self::update_credit_card_customer(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    self::_prep_api_27_data($customerId, $result['applicant']),
                    $result['applicant'],
                );
            }


            $proceedFive = CreditCardService::beforeCallApi(5, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
            $responseDataFive = null;
            if ($proceedFive) {
                $responseDataFive = json_decode($proceedFive->response, true);
            } else {
                $responseDataFive = self::update_credit_card_contract(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    self::_prep_api_29_data($customerContractNo, $result),
                    $result['applicant'],
                );
            }

            $proceedSix = CreditCardService::beforeCallApi(6, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
            $responseDataSix = null;
            if ($proceedSix) {
                $responseDataSix = json_decode($proceedSix->response, true);
            } else {
                $responseDataSix = self::update_credit_card(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    $customerCardNo,
                    $result['applicant']->source_user_id,
                    $result['applicant']->card_fees_profile,
                    $result['applicant'],
                );
            }

            $proceedSeven = CreditCardService::beforeCallApi(7, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
            $responseDataSeven = null;
            if ($proceedSeven) {
                $responseDataSeven = json_decode($proceedSeven->response, true);
            } else {
                $responseDataSeven = self::addCardMemo(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    $customerCardNo,
                    $result['applicant'],
                );
            }



            $proceedEight = CreditCardService::beforeCallApi(8, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
            $responseDataEight = null;
            if ($proceedEight) {
                $responseDataEight = json_decode($proceedEight->response, true);
            } else {
                $responseDataEight = self::addCardMobileNumber(
                    $file_info_id,
                    $result['applicant']->file_sl_no,
                    [
                        'mobile_no'         => $result['applicant']->mobile_no,
                        'account_number'    => $accountNumber,
                        'pan'               => $customerCardNo,
                        'client_id'         => $customerId,
                    ],
                    $result['applicant'],
                );
            }

            if (!empty($result['applicant']->email)) {
                $proceedNine = CreditCardService::beforeCallApi(9, $result['applicant']->lead_id, $result['applicant']->file_info_id, $result['applicant']->candidate_info_id);
                $responseDataNine = null;
                if ($proceedNine) {
                    $responseDataNine = json_decode($proceedNine->response, true);
                } else {
                    $responseDataNine = self::addCardEmailAdress(
                        $file_info_id,
                        $result['applicant']->file_sl_no,
                        [
                            'email_address'     => $result['applicant']->email,
                            'account_number'    => $accountNumber,
                            'pan'               => $customerCardNo,
                            'client_id'         => $customerId,
                        ],
                        $result['applicant'],
                    );
                }
            }


            // DB::table('FILE_INFO')->where('ID', $file_info_id)->update([
            //     'CLIENT_ID'         => $customerId,
            //     'CONTRACT_NO'       => $customerContractNo,
            //     'CARD_NO_MASKED'    => isset($customerCardNo) ? maskCardNumber($customerCardNo) : '',
            // ]);
            // DB::table('CANDIDATE_INFO')->where('ID', $result['applicant']->id)->update([
            //     'CUSTOMER_ID_CMS'   => $customerId,
            //     'CONTRACT_NO'       => $customerContractNo,
            //     'CARD_NO_MASKED'    => isset($customerCardNo) ? maskCardNumber($customerCardNo) : '',
            // ]);

            $suppleProductId = '102030';
            if (count($result['supplementary']) > 0 && !empty($suppleProductId)) {

                foreach ($result['supplementary'] as $key => $supplementary) {

                    $proceedSupOne = CreditCardService::beforeCallApi(10, $supplementary->lead_id, $supplementary->file_info_id, $supplementary->candidate_info_id);
                    $responseDataSupOne = null;
                    if ($proceedSupOne) {
                        $responseDataSupOne = json_decode($proceedSupOne->response, true);
                    } else {
                        $responseDataSupOne = self::create_credit_card_customer(
                            $file_info_id,
                            $result['applicant']->file_sl_no,
                            $supplementary->candidate_title,
                            $supplementary->candidate_name,
                            $supplementary->name_transliteration,
                            '1',  //customerType. Fixed Value
                            $supplementary->gender,
                            $supplementary->dob,
                            '2', //$documentMaskingBy. Fixed Value
                            's' . ($key + 1) . $result['applicant']->etin,
                            $supplementary->father_name,
                            $supplementary->mother_name,
                            $supplementary,
                        );
                    }

                    $customerIdSupple = $responseDataSupOne['customerId'] ?? '';
                    if (!empty($customerIdSupple)) {

                        $proceedSupTwo = CreditCardService::beforeCallApi(4, $supplementary->lead_id, $supplementary->file_info_id, $supplementary->candidate_info_id);
                        $responseDataSupTwo = null;
                        if ($proceedSupTwo) {
                            $responseDataSupTwo = json_decode($proceedSupTwo->response, true);
                        } else {
                            $responseDataSupTwo = self::update_credit_card_customer(
                                $file_info_id,
                                $result['applicant']->file_sl_no,
                                self::_prep_api_27_data($customerId, $supplementary),
                                $supplementary,
                            );
                        }

                        $proceedSupThree = CreditCardService::beforeCallApi(11, $supplementary->lead_id, $supplementary->file_info_id, $supplementary->candidate_info_id);
                        $responseDataSupThree = null;
                        if ($proceedSupThree) {
                            $responseDataSupThree = json_decode($proceedSupThree->response, true);
                        } else {
                            $responseDataSupThree = self::supplementary_card_creation(
                                $file_info_id,
                                $supplementary->file_sl_no,
                                $customerCardNo,
                                $suppleProductId,
                                $customerIdSupple,
                                $customerContractNo,
                                $supplementary,
                            );
                        }
                        $cardNumber = $responseDataSupThree['cardNumber'] ?? '';

                        if (!empty($cardNumber)) {
                            $proceedSupFour = CreditCardService::beforeCallApi(8, $supplementary->lead_id, $supplementary->file_info_id, $supplementary->candidate_info_id);
                            $responseDataSupThree = null;
                            if ($proceedSupFour) {
                                $responseDataSupThree = json_decode($proceedSupFour->response, true);
                            } else {
                                $responseDataSupThree = self::addCardMobileNumber(
                                    $file_info_id,
                                    $supplementary->file_sl_no,
                                    [
                                        'mobile_no'         => $supplementary->mobile_no,
                                        'account_number'    => $accountNumber,
                                        'pan'               => $cardNumber,
                                        'client_id'         => $customerIdSupple,
                                    ],
                                    $supplementary,
                                );
                            }
                            if (!empty($supplementary->email)) {
                                $proceedSupFive = CreditCardService::beforeCallApi(9, $supplementary->lead_id, $supplementary->file_info_id, $supplementary->candidate_info_id);
                                $responseDataSupFive = null;
                                if ($proceedSupFive) {
                                    $responseDataSupFive = json_decode($proceedSupFive->response, true);
                                } else {
                                    $responseDataSupFive = self::addCardEmailAdress(
                                        $file_info_id,
                                        $supplementary->file_sl_no,
                                        [
                                            'email_address'     => $supplementary->email,
                                            'account_number'    => $accountNumber,
                                            'pan'               => $cardNumber,
                                            'client_id'         => $customerIdSupple,
                                        ],
                                        $supplementary,
                                    );
                                }
                            }
                        }
                    }
                }
            }
            return [
                'status' => 1,
                'message' => 'API Successful'
            ];
        }
    }





    // New To Bank API all Function



    public static function create_client_credit_card(
        $fileId,
        $fileNo,
        $applicant_title,
        $applicant_name,
        $name_transliteration,
        $gender,
        $dob,
        $doc_masking_key,
        $doc_masking_no,
        $contract_type_id,
        $data
    ) {

        $requestPayload = [
            "customerTitle"         => $applicant_title,
            "customerName"          => $applicant_name,
            "customerTransName"     => $name_transliteration,
            "gender"                => $gender,
            "dob"                   => $dob,
            "documentMaskingBy"     => (string) $doc_masking_key,
            "documentMaskingNumber" => (string) $doc_masking_no,
            "contractTypeId"        => (string) $contract_type_id,
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

    public static function update_credit_card_customer($fileId, $fileNo, $card_info, $data)
    {
        $requestPayload = [
            'customerId'                      => $card_info['customerId'] ?? '',
            'customerPhoneNo'                 => $card_info['customerPhoneNo'] ?? '',
            'customerEmail'                   => $card_info['customerEmail'] ?? '',
            'customerMobileNo'                => $card_info['customerMobileNo'] ?? '',
            'customerFaxNo'                   => '',
            'fatherName'                      => $card_info['fatherName'] ?? '',
            'motherName'                      => $card_info['motherName'] ?? '',
            'spouse'                          => $card_info['spouse'] ?? '',
            'placeOfBirth'                    => $card_info['placeOfBirth'] ?? '',
            'regCountry'                      => !empty($card_info['regFullAddress']) ? '50' : '',
            'regAddressRegion'                => $card_info['regAddressRegion'] ?? '',
            'regAddressCity'                  => $card_info['regAddressCity'] ?? '',
            'regZipCode'                      => $card_info['regZipCode'] ?? '',
            'regFullAddress'                  => $card_info['regFullAddress'] ?? '',
            'residenceCountry'                => !empty($card_info['residenceFullAddress']) ? '50' : '',
            'residenceRegion'                 => $card_info['residenceRegion'] ?? '',
            'residenceCity'                   => $card_info['residenceCity'] ?? '',
            'residenceZipCode'                => $card_info['residenceZipCode'] ?? '',
            'residenceFullAddress'            => $card_info['residenceFullAddress'] ?? '',
            'correspondenceCountry'           => !empty($card_info['correspondenceFullAddress']) ? '50' : '',
            'correspondenceRegion'            => $card_info['correspondenceRegion'] ?? '',
            'correspondenceCity'              => $card_info['correspondenceCity'] ?? '',
            'correspondenceZipCode'           => $card_info['correspondenceZipCode'] ?? '',
            'correspondenceFullAddress'       => $card_info['correspondenceFullAddress'] ?? '',
            'residentStatus'                  => $card_info['residentStatus'] ?? '',
            'educationStatus'                 => $card_info['educationStatus'] ?? '',
            'maritalStatus'                   => $card_info['maritalStatus'] ?? '',
            'occupationStatus'                => $card_info['occupationStatus'] ?? '',
            'customerNationality'             => $card_info['customerNationality'] ?? '',
            'employeeId'                      => $card_info['employeeId'] ?? '',
            'company'                         => '',
            'department'                      => '',
            'position'                        => $card_info['position'] ?? '',
            'tin'                             => $card_info['tin'] ?? '',
            'nid'                             => $card_info['nid'] ?? '',
            'smartCardNo'                     => $card_info['smartCardNo'] ?? '',
            'passportNo'                      => $card_info['passportNo'] ?? '',
            'passportName'                    => $card_info['passportName'] ?? '',
            'passportAddress'                 => $card_info['passportAddress'] ?? '',
            'passportIssueDate'               => $card_info['passportIssueDate'] ?? '',
            'passportIssueExpiryDate'         => $card_info['passportIssueExpiryDate'] ?? '',
            'passportIssuePlace'              => $card_info['passportIssuePlace'] ?? '',
            'passportIssueCounty'             => $card_info['passportIssueCounty'] ?? '',
            'applicationSource'               => $card_info['applicationSource'] ?? '',
            'applicationReceiveDate'          => $card_info['applicationReceiveDate'],
            'applicationAcceptDate'           => $card_info['applicationAcceptDate'],
            'applicationNo'                   => $card_info['applicationNo'] ?? '',
            'rmCode'                          => $card_info['rmCode'] ?? '',
            'documentIssueCountry'            => $card_info['documentIssueCountry'] ?? '',
            'collectionCallSensitiveCustomer' => $card_info['collectionCallSensitiveCustomer'] ?? '',
            'taxReturnAssessmentYear'         => $card_info['taxReturnAssessmentYear'],
            'receiveCardThrough'              => $card_info['receiveCardThrough'] ?? '',
            'branchNameToReceiveCard'         => $card_info['branchNameToReceiveCard'] ?? '',
        ];

        $response = [
            "serviceId"            => "253511506762",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful.",
            "channelTransactionId" => "LOS251217395023184240",
            "timeStamp"            => "17-12-2025 15:06:18",
            "errorMessage"         => null
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

    public static function update_credit_card_contract($fileId, $fileNo, $card_info, $data)
    {

        $requestPayload = [
            'contractNumber'             => $card_info['contractNumber'] ?? '',
            'RFCApproveLimit'            => $card_info['RFCApproveLimit'] ?? '',
            'thirdPartyAgent'            => $card_info['thirdPartyAgent'] ?? '',
            'autoDebitEFTNInstruction'   => $card_info['autoDebitEFTNInstruction'] ?? '',
            'cityShield'                 => $card_info['cityShield'] ?? '',
            'securityLIENInfo'           => $card_info['securityLIENInfo'] ?? '',
            'receiveCardFrom'            => $card_info['receiveCardFrom'] ?? '',
            'receiveBranch'              => $card_info['receiveBranch'] ?? '',
            'applicationFileNoCACV'      => "STP-" . $card_info['applicationFileNoCACV'] ?? '',
            'collateralValueBDT'         => $card_info['collateralValueBDT'] ?? '',
            'collateralValueUSD'         => $card_info['collateralValueUSD'] ?? '',
            'applicationFileNoLE'        => $card_info['applicationFileNoLE'] ?? '',
            'referenceName'              => $card_info['referenceName'] ?? '',
            'refereceAddress'            => $card_info['refereceAddress'] ?? '',
            'referencePhoneNumber'       => $card_info['referencePhoneNumber'] ?? '',
            'secondReferenceName'        => $card_info['secondReferenceName'] ?? '',
            'secondReferenceAddress'     => $card_info['secondReferenceAddress'] ?? '',
            'secondReferencePhoneNumber' => $card_info['secondReferencePhoneNumber'] ?? '',
            'nameOfFirstNominee'         => $card_info['nameOfFirstNominee'] ?? '',
            'firstNomineePercentage'     => $card_info['firstNomineePercentage'] ?? '',
            'nameOfSecondNominee'        => $card_info['nameOfSecondNominee'] ?? '',
            'secondNomineePercentage'    => $card_info['secondNomineePercentage'] ?? '',
            'dBRRange'                   => isset($card_info['dBRRange']) && $card_info['dBRRange'] > 0 ? $card_info['dBRRange'] . '%' : '',
            'approverName'               => $card_info['approverName'] ?? '',
            'corporateName'              => $card_info['corporateName'] ?? '',
            'liabilityType'              => $card_info['liabilityType'] ?? '',
            'billingPaymentOption'       => $card_info['billingPaymentOption'] ?? '',
            'receiveStatementThrough'    => $card_info['receiveStatementThrough'] ?? '',
            'cASAAccountScheme'          => $card_info['cASAAccountScheme'] ?? '',
            'welcomeGift'                => $card_info['welcomeGift'] ?? '',
            'giftEntryDate'              => $card_info['giftEntryDate'] ?? '',
            'campaign'                   => $card_info['campaign'] ?? '',
            'requiredChequeBook'         => $card_info['requiredChequeBook'] ?? '',
            'specialRemarks'             => "0",
            'finProfile'                 => $card_info['finProfile'],
            'rmCode'                     => $card_info['rmCode'],
            'applicationSource'          => $card_info['applicationSource'],
            'applicationAcceptDate'      => $card_info['applicationAcceptDate'] ?? '',
            'applicationReceiveDate'     => '',
        ];

        $response = [
            "serviceId"            => "253511506763",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful.",
            "channelTransactionId" => "LOS251217971700538845",
            "timeStamp"            => "17-12-2025 15:06:20",
            "errorMessage"         => null
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 5,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
        return $response;
    }

    public static function update_credit_card($fileId, $fileNo, $pan, $sourceCode, $finProfile, $data)
    {

        $requestPayload = [
            'cardNo'            => $pan ?? '',
            'rmCode'            => $sourceCode ?? '',
            'applicationCode'   => '',
            'applicationSource' => '',
            'cardSourceChannel' => '',
            'finProfile'        => $finProfile,
        ];

        $response = [
            "serviceId"            => "253511506764",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful.",
            "channelTransactionId" => "LOS251217140812380572",
            "timeStamp"            => "17-12-2025 15:06:23",
            "errorMessage"         => null
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 6,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
        return $response;
    }

    public static function addCardMemo($fileId, $fileNo, $cardNo, $data)
    {
        $requestPayload = [
            "cardNo"                => $cardNo,
            "memoText"              => "Test Memo from API",
            "channelTransactionId"  => "LOS251217246537486530",
        ];

        $response = [
            "serviceId"            => "253511506765",
            "timeStamp"            => "17-12-2025 15:06:28",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful.",
            "channelTransactionId" => "LOS251217000091694098",
            "errorMessage"         => null
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 7,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
        return $response;
    }

    public static function addCardMobileNumber($fileId, $fileNo, $data, $data1)
    {
        $requestPayload = [
            'mobileNo'              => $data['mobile_no'], //'01303021359',
            'accountNumber'         => $data['account_number'], // '',
            'pan'                   => $data['pan'], // '371599207576333',
            'clientId'              => $data['client_id'], // '2320771',
            "channelTransactionId"  => 'LOS' . date('ymd') . substr($fileNo, -7) . rand(100000, 999999),
        ];

        $response = [
            "serviceId"            => "253511506766",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful.",
            "channelTransactionId" => "LOS251217000091650718",
            "timeStamp"            => "17-12-2025 15:06:36"
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 8,
            'lead_id'           => $data1->lead_id,
            'file_info_id'      => $data1->file_info_id,
            'candidate_info_id' => $data1->candidate_info_id,
            'file_sl_no'        => $data1->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
        return $response;
    }

    public static function addCardEmailAdress($fileId, $fileNo, $data, $data1)
    {

        $requestPayload = [
            'emailAddress'          => $data['email_address'], //'shajal@gmail.com',
            'accountNumber'         => $data['account_number'], // '',
            'pan'                   => $data['pan'], // '371599207576333',
            'clientId'              => $data['client_id'], // '2320771',
            "channelTransactionId"  => 'LOS' . date('ymd') . substr($fileNo, -7) . rand(100000, 999999),
        ];

        $response = [
            "serviceId"            => "253511506767",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful.",
            "channelTransactionId" => "LOS251217000091651928",
            "timeStamp"            => "17-12-2025 15:06:44"
        ];
        CommonService::pushAPILogInQueue([
            'api_id'            => 9,
            'lead_id'           => $data1->lead_id,
            'file_info_id'      => $data1->file_info_id,
            'candidate_info_id' => $data1->candidate_info_id,
            'file_sl_no'        => $data1->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
    }

    public static function create_credit_card_customer(
        $fileId,
        $fileNo,
        $customerTitle,
        $customerName,
        $customerTransName,
        $customerType,
        $gender,
        $dob,
        $documentMaskingBy,
        $documentMaskingNumber,
        $fatherName,
        $motherName,
        $data
    ) {
        $requestPayload = [
            "customerTitle"         => $customerTitle,
            "customerName"          => $customerName,
            "customerTransName"     => $customerTransName,
            "customerType"          => $customerType,
            "gender"                => $gender,
            "dob"                   => $dob,
            "documentMaskingBy"     => $documentMaskingBy,
            "documentMaskingNumber" => $documentMaskingNumber,
            "fatherName"            => $fatherName,
            "motherName"            => $motherName,
        ];

        $response = [
            "customerId"           => "2668093",
            "channelTransactionId" => "LOS251210773535705841",
            "serviceId"            => "fa7d687f-e9fd-448e-a7a9-489fa4b095f5",
            "timestamp"            => "10-12-2025 17:11:10",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful."
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 10,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
        return $response;
    }


    public static function supplementary_card_creation($fileId, $fileNo, $parentPan, $productId, $customerIdSupple, $contractIdPrimary, $data)
    {

        $requestPayload = [
            "parentPan" => $parentPan,
            "productId" => $productId,
            "customerId" => $customerIdSupple,
            "contractId" => $contractIdPrimary,
        ];
        $response = [
            "cardNumber"           => "376948122677482",
            "channelTransactionId" => "LOS251219173955896537",
            "serviceId"            => "7c3f3b2e-5f4e-4f4b-8f4e-2e5f4e4b8f4e",
            "timestamp"            => "19-12-2025 17:40:00",
            "responseCode"         => "100",
            "responseMessage"      => "Operation Successful."
        ];

        CommonService::pushAPILogInQueue([
            'api_id'            => 11,
            'lead_id'           => $data->lead_id,
            'file_info_id'      => $data->file_info_id,
            'candidate_info_id' => $data->candidate_info_id,
            'file_sl_no'        => $data->file_sl_no,
            'requestPayload'    => $requestPayload,
            'response'          => $response,
        ]);
    }

    // Prepare API Data Functions
    public static function _prep_api_27_data($customerId, $item)
    {
        // Format registration address [Applicant Address Information company_name, business_name, designation_name, registration_address]
        // note : company_name and business_name are mutually exclusive
        $companyName     = $item->company_name ?? '';
        $businessName    = $item->business_name ?? '';
        $designationName = "Programmer";
        $finalName       = $companyName ?: $businessName;
        $mainPart        = implode(', ', array_filter([$designationName, $finalName]));
        if (!empty($mainPart) && !empty($item->registration_address)) {
            $fullText = $mainPart . ' * ' . $item->registration_address;
        } else {
            $fullText = $mainPart ?: $item->registration_address;
        }
        $item->registration_address = $fullText;
        // End Format registration address

        $data = [
            'customerId' => $customerId ?? '',
            'customerPhoneNo' => $item->phone_no ?? '',
            'customerEmail' => $item->email ?? '',
            'customerMobileNo' => $item->mobile_no ?? '',
            'customerFaxNo' => $item->fax_no ?? '',
            'fatherName' => $item->father_name ?? '',
            'motherName' => $item->mother_name ?? '',
            'spouse' => $item->spouse_name ?? '',
            'placeOfBirth' => $item->place_of_birth ?? '',
            'regCountry' => $item->registration_country_code ?? '',
            'regAddressRegion' => $item->registration_region_code ?? '',
            'regAddressCity' => $item->registration_city_code ?? '',
            'regZipCode' => $item->registration_postal_code ?? '',
            'regFullAddress' => $item->registration_address,
            'residenceCountry' => $item->residence_country_code ?? '',
            'residenceRegion' => $item->residence_region_code ?? '',
            'residenceCity' => $item->residence_city_code ?? '',
            'residenceZipCode' => $item->residence_postal_code ?? '',
            'residenceFullAddress' => $item->residence_address ?? '',
            'correspondenceCountry' => $item->registration_country_code ?? '',
            'correspondenceRegion' => $item->registration_region_code ?? '',
            'correspondenceCity' => $item->registration_city_code ?? '',
            'correspondenceZipCode' => $item->registration_postal_code ?? '',
            'correspondenceFullAddress' => $item->registration_address ?? '',
            'residentStatus' => $item->residential_status ?? '',
            'educationStatus' => $item->education_level ?? '',
            'maritalStatus' => $item->marital_status ?? '',
            'occupationStatus' => $item->occupation_code ?? '',
            // 'occupationStatus' => self::getOccupationCode($item->customer_segment_id),
            'customerNationality' => $item->nationality     ?? '',
            'employeeId' => $item->staff_id ?? '',
            'company' => $item->company_name ?? '',
            'department' => $item->department_name ?? '',
            'position' =>  'sdfsf',
            'tin' => 'abc12345',
            'nid' => 'ksdkfsd1234',
            'smartCardNo' => $item->smart_card ?? '',
            'passportNo' => $item->passport_no ?? '',
            'passportName' => $item->name_on_passport ?? '',
            'passportAddress' => $item->address_on_passport ?? '',
            'passportIssueDate' => $item->passport_issuing_date ?? '',
            'passportIssueExpiryDate' => $item->passport_expiry_date ?? '',
            'passportIssuePlace' => $item->passport_issue_place ?? '',
            'passportIssueCounty' => $item->passport_issuing_country_name ?? '',
            'applicationSource' => $item->application_source_id ?? '',
            'applicationReceiveDate' => $item->entry_date ?? '',
            'applicationAcceptDate' => $item->approve_date ?? '',
            'applicationNo' => "STP-" . $item->file_sl_no ?? '',
            'rmCode' => $item->source_user_id ?? '',
            'documentIssueCountry' => $item->document_issue_country_id ?? '',
            'collectionCallSensitiveCustomer' => $item->ccs_customer_type_id ?? '',
            // 'taxReturnAssessmentYear' => self::normalizeYearRange($item->tax_return_assessment_year),
            'taxReturnAssessmentYear' => $item->tax_return_assessment_year ?? '',
            'receiveCardThrough' => $item->receive_card_through ?? '',
            'branchNameToReceiveCard' => $item->br_id_to_receive_card ?? '',
        ];

        return $data;
    }

    public static function _prep_api_29_data($customerContractNo, $result)
    {
        return [
            'contractNumber' => $customerContractNo,
            'RFCApproveLimit' => (string) $result['applicant']->approved_amount,
            'thirdPartyAgent' => (string) $result['applicant']->third_party_agent,
            'autoDebitEFTNInstruction' => (string) $result['applicant']->auto_debit_eftn_instruction,
            'cityShield' =>  (string) $result['applicant']->city_shield,
            'securityLIENInfo' => (string) $result['applicant']->security_lien_info,
            'receiveCardFrom' => (string) $result['applicant']->receive_card_through,
            'receiveBranch' => (string) $result['applicant']->br_id_to_receive_card,
            'applicationFileNoCACV' => (string) $result['applicant']->existing_file_sl_no_calc,  // File SL No. For LE, current file SL No
            'collateralValueBDT' => (string) $result['applicant']->collateral_value_local,
            'collateralValueUSD' => (string) $result['applicant']->collateral_value_foreign,
            'applicationFileNoLE' => '',  // File SL No. For LE, current file SL No
            // 'applicationFileNoLE' => (string) $result['applicant']->existing_file_sl_no_calc,  // File SL No. For LE, current file SL No
            'referenceName' => (string) $result['applicant']->reference_name,
            'refereceAddress' => (string) $result['applicant']->reference_address,
            'referencePhoneNumber' => (string) $result['applicant']->reference_phone_number,
            'secondReferenceName' => (string) $result['applicant']->second_reference_name,
            'secondReferenceAddress' => (string) $result['applicant']->second_reference_address,
            'secondReferencePhoneNumber' => (string) $result['applicant']->second_reference_phone_number,
            'nameOfFirstNominee' => (string) $result['applicant']->name_of_first_nominee,
            'firstNomineePercentage' => (string) $result['applicant']->first_nominee_percentage,
            'nameOfSecondNominee' => (string) $result['applicant']->name_of_second_nominee,
            'secondNomineePercentage' => (string) $result['applicant']->second_nominee_percentage,
            'dBRRange' => (string) $result['applicant']->recommended_dbr,
            'approverName' => (string) $result['applicant']->approver_name,
            // 'corporateName' => (string) $result['applicant']->corporate_field_mapping,
            'corporateName' => "",
            'liabilityType' => (string) $result['applicant']->liability_type,
            'billingPaymentOption' => (string) $result['applicant']->billing_payment_option,
            'receiveStatementThrough' => (string) $result['applicant']->receive_statement_through,
            'cASAAccountScheme' => (string) $result['applicant']->casa_account_scheme,
            'welcomeGift' => (string) $result['applicant']->welcome_gift,
            'giftEntryDate' => (string) $result['applicant']->gift_entry_date,
            'campaign' => (string) $result['applicant']->campaign_id,
            'requiredChequeBook' => (string) $result['applicant']->required_cheque_book,
            'specialRemarks' => "0",  // FIXED VALUE
            // 'specialRemarks' => (string) $result['applicant']->special_remarks,
            'finProfile' => (string) $result['applicant']->card_fees_profile,
            'rmCode' => (string) $result['applicant']->source_user_id,
            'applicationSource' => (string) $result['applicant']->application_source_id,
            'applicationAcceptDate' => (string) $result['applicant']->approve_date,
        ];
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
