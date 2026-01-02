<?php

namespace App\Services\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\Http;
// use Exception;
use App\Services\API\ApiRequestService;
use App\Services\CommonService;

class CreditCardService
{
    public static function processCardAPI($request)
    {
        $result = self::prepareCardAPIData($request->file_info_id);  
        

        // // Process Limit Enhancement cards
        // if ($result['applicant']->is_le_card == 1) {
        //     return self::_processLE($request->file_info_id, $result);
        // }


        if (empty($result['applicant']->customer_id_cms)) {
            $resp = self::cleintIdByTin($result['applicant']->file_id, $result['applicant']->file_sl_no, $result['applicant']->etin);
            $item = json_decode($resp['resp'], true);
            $clientId = $item['responseData'][0]['clientId'] ?? '';

            if(!empty($clientId)){
                // Update cliendId and process Existing-to-Bank
                // --------------------------------------------------
                // 1. Update FILE_INFO
                DB::table('FILE_INFO')
                    ->where('ID', $request->file_info_id)
                    ->update(['CLIENT_ID' => $clientId]);

                // 2. Update CANDIDATE_INFO
                DB::table('CANDIDATE_INFO')
                    ->where('FILE_INFO_ID', $request->file_info_id)
                    ->where('CANDIDATE_TYPE', 'Applicant')
                    ->update([
                        'CUSTOMER_ID_CMS' => $clientId
                    ]);
                
                // 3. Update customer_id_cms of Applicant data
                $result['applicant']->customer_id_cms = $clientId;

                // 4. Call E2B process
                return self::_processE2B($request->file_info_id, $result);
            }

            // Process New-to-Bank applicants (as CMS ID is not found)
            return self::_processN2B($request->file_info_id, $result);
        }
        
        // Process Existing-to-Bank (as CMS ID is available)
        return self::_processE2B($request->file_info_id, $result);
    }

    private static function _processN2B($file_info_id, $result)
    {
        // $getEmptyFields = self::getEmptyFields($result);
        // if (!empty($getEmptyFields)) {
        //     return ['validation' => $getEmptyFields];
        // }

        $api_28_response = $api_46_response = $api_27_response = $api_29_response = $api_56_response = $api_26_response = $api_47_response = [];

        $api_28_response = self::create_client_credit_card(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $result['applicant']->candidate_title,
            $result['applicant']->candidate_name,
            $result['applicant']->name_transliteration,
            $result['applicant']->gender,
            $result['applicant']->dob,
            2,  // doc_masking_key. Fixed value: 2=TIN
            $result['applicant']->etin,  // doc_masking_no.  TIN
            $result['applicant']->tz_product_id,  // contract_type_id
        );
        $arr = json_decode($api_28_response['resp'], true);
        $customerId = $arr['customerId'] ?? '';
        $customerContractNo = $arr['contractNo'] ?? '';
        $customerCardNo = $arr['pan'] ?? '';
        $accountNumber = $arr['accountNumber'] ?? '';

        if (empty($customerId) || empty($customerContractNo) || empty($customerCardNo)) {
            return [
                'status' => 0,
                'message' => 'API Faied'
            ];
        }

        $api_46_response = self::set_credit_card_contract_limit(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerContractNo,
            $customerCardNo,
            $result['applicant']->approved_amount
        );

        $api_27_response = self::update_credit_card_customer(
            $file_info_id,
            $result['applicant']->file_sl_no,
            self::_prep_api_27_data($customerId, $result['applicant'])
        );

        $api_29_response = self::update_credit_card_contract(
            $file_info_id,
            $result['applicant']->file_sl_no,
            self::_prep_api_29_data($customerContractNo, $result)
        );

        $api_56_response = self::update_credit_card(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerCardNo,
            $result['applicant']->source_user_id,
            $result['applicant']->card_fees_profile
        );

        $api_73_response = self::addCardMemo(            
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerCardNo
        );

        $api_84_response = self::addCardMobileNumber(
             $file_info_id
            , $result['applicant']->file_sl_no
            , [
                'mobile_no'         => $result['applicant']->mobile_no,
                'account_number'    => $accountNumber,
                'pan'               => $customerCardNo,
                'client_id'         => $customerId,
            ]
        );

        if(!empty($result['applicant']->email)){
            $api_85_response = self::addCardEmailAdress(
                $file_info_id
                , $result['applicant']->file_sl_no
                , [
                    'email_address'     => $result['applicant']->email,
                    'account_number'    => $accountNumber,
                    'pan'               => $customerCardNo,
                    'client_id'         => $customerId,
                ]
            );
        }        

        DB::table('FILE_INFO')->where('ID', $file_info_id)->update([
            'CLIENT_ID'         => $customerId,
            'CONTRACT_NO'       => $customerContractNo,
            'CARD_NO_MASKED'    => isset($customerCardNo) ? maskCardNumber($customerCardNo) : '',
        ]);
        DB::table('CANDIDATE_INFO')->where('ID', $result['applicant']->id)->update([
            'CUSTOMER_ID_CMS'   => $customerId,
            'CONTRACT_NO'       => $customerContractNo,
            'CARD_NO_MASKED'    => isset($customerCardNo) ? maskCardNumber($customerCardNo) : '',
        ]);

        $suppleProductId = self::getSuppleProductId($result['applicant']->product_id, $result['applicant']->tz_product_id);
        
        if (count($result['supplementary']) > 0 && !empty($suppleProductId)) {
            foreach ($result['supplementary'] as $key => $supplementary) {
                $api_26_response = self::create_credit_card_customer(
                    $file_info_id,
                    $supplementary->file_sl_no,
                    $supplementary->candidate_title,
                    $supplementary->candidate_name,
                    $supplementary->name_transliteration,
                    '1',  //customerType. Fixed Value
                    $supplementary->gender,
                    $supplementary->dob,
                    '2', //$documentMaskingBy. Fixed Value
                    's'.($key+1). $result['applicant']->etin,   // $supplementary->etin,
                    $supplementary->father_name,
                    $supplementary->mother_name
                );                

                $response_26_data = json_decode($api_26_response['resp'], true);
                $customerIdSupple = $response_26_data['customerId'] ?? '';

                if(!empty($customerIdSupple)){                    
                    $api_27_response = self::update_credit_card_customer(
                        $file_info_id,
                        $result['applicant']->file_sl_no,
                        self::_prep_api_27_data($customerIdSupple, $supplementary)
                    );

                    $api_47_response = self::supplementary_card_creation(
                        $file_info_id,
                        $supplementary->file_sl_no,
                        $customerCardNo,
                        $suppleProductId,     // $supplementary->tz_product_id,
                        $customerIdSupple,
                        $customerContractNo
                    );

                    $response_data = json_decode($api_47_response['resp'], true);
                    $cardNumber = $response_data['cardNumber'] ?? '';

                    if(!empty($cardNumber)){
                        // [Pervez Bhai: Dec 10, 2025]: Applicant's accountNumber, Supples's cardNumber and customerId : Start =-
                        $api_84_response = self::addCardMobileNumber(
                            $file_info_id
                            , $supplementary->file_sl_no
                            , [
                                'mobile_no'      => $supplementary->mobile_no,
                                'account_number' => $accountNumber,
                                'pan'            => $cardNumber,
                                'client_id'      => $customerIdSupple,
                            ]
                        );
                        
                        if(!empty($supplementary->email)){
                            
                            $api_85_response = self::addCardEmailAdress(
                                $file_info_id
                                , $supplementary->file_sl_no
                                , [
                                    'email_address'  => $supplementary->email,
                                    'account_number' => $accountNumber,
                                    'pan'            => $cardNumber,
                                    'client_id'      => $customerIdSupple,
                                ]
                            );
                        }    
                        // [Pervez Bhai: Dec 10, 2025]: Applicant's accountNumber, Supples's cardNumber and customerId : End 
                    }

                    DB::table('CANDIDATE_INFO')->where('ID', $supplementary->id)->update([
                        'CUSTOMER_ID_CMS'   => $customerIdSupple,
                        // 'CONTRACT_NO'       => $customerContractNo,
                        'CARD_NO_MASKED'    => !empty($cardNumber) ? maskCardNumber($cardNumber) : '',
                    ]);
                }
            }
        }

        return [
            'status' => 1,
            'message' => 'API Successed'
        ];
    }

    private static function _processE2B($file_info_id, $result)
    {
        // $getEmptyFields = self::getEmptyFields($result);
        // if (!empty($getEmptyFields)) {
        //     return ['validation' => $getEmptyFields];
        // }
        
        $api_49_response = $api_46_response = $api_27_response = $api_29_response = $api_56_response = $api_26_response = $api_47_response = [];

        $api_49_response = self::create_credit_card_contract_with_card(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $result['applicant']->tz_product_id,  // contract_type_id
            $result['applicant']->customer_id_cms
        );
        $arr = json_decode($api_49_response['resp'], true);
        $customerId = $result['applicant']->customer_id_cms ?? '';
        $customerContractNo = $arr['contractNumber'] ?? '';
        $customerCardNo = $arr['cardNumber'] ?? '';
        $accountNumber = $arr['accountNumber'] ?? '';

        if (empty($customerContractNo) || empty($customerCardNo)) {
            return;
        }

        $api_46_response = self::set_credit_card_contract_limit(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerContractNo,
            $customerCardNo,
            $result['applicant']->approved_amount
        );

        $api_27_response = self::update_credit_card_customer(
            $file_info_id,
            $result['applicant']->file_sl_no,
            self::_prep_api_27_data($customerId, $result['applicant'])
        );

        $api_29_response = self::update_credit_card_contract(
            $file_info_id,
            $result['applicant']->file_sl_no,
            self::_prep_api_29_data($customerContractNo, $result)
        );

        $api_56_response = self::update_credit_card(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerCardNo,
            $result['applicant']->source_user_id,
            $result['applicant']->card_fees_profile
        );
        
        $api_73_response = self::addCardMemo(            
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerCardNo
        );

        $api_84_response = self::addCardMobileNumber(
             $file_info_id
            , $result['applicant']->file_sl_no
            , [
                'mobile_no'         => $result['applicant']->mobile_no,
                'account_number'    => $accountNumber,
                'pan'               => $customerCardNo,
                'client_id'         => $customerId,
            ]
        );

        if(!empty($result['applicant']->email)){
            $api_85_response = self::addCardEmailAdress(
                $file_info_id
                , $result['applicant']->file_sl_no
                , [
                    'email_address'     => $result['applicant']->email,
                    'account_number'    => $accountNumber,
                    'pan'               => $customerCardNo,
                    'client_id'         => $customerId,
                ]
            );
        }

        DB::table('FILE_INFO')->where('ID', $file_info_id)->update([
            'CLIENT_ID'         => $customerId,
            'CONTRACT_NO'       => $customerContractNo,
            'CARD_NO_MASKED'    => isset($customerCardNo) ? maskCardNumber($customerCardNo) : '',
        ]);
        DB::table('CANDIDATE_INFO')->where('ID', $result['applicant']->id)->update([
            'CUSTOMER_ID_CMS'   => $customerId,
            'CONTRACT_NO'       => $customerContractNo,
            'CARD_NO_MASKED'    => isset($customerCardNo) ? maskCardNumber($customerCardNo) : '',
        ]);

        $suppleProductId = self::getSuppleProductId($result['applicant']->product_id, $result['applicant']->tz_product_id);
        
        if (count($result['supplementary']) > 0 && !empty($suppleProductId)) {
            foreach ($result['supplementary'] as $key => $supplementary) {
                $api_26_response = self::create_credit_card_customer(
                    $file_info_id,
                    $supplementary->file_sl_no,
                    $supplementary->candidate_title,
                    $supplementary->candidate_name,
                    $supplementary->name_transliteration,
                    '1',  //customerType. Fixed Value
                    $supplementary->gender,
                    $supplementary->dob,
                    '2', //$documentMaskingBy. Fixed Value
                    // $supplementary->etin, 
                    's'.($key+1). $result['applicant']->etin,
                    $supplementary->father_name,
                    $supplementary->mother_name
                );

                $response_26_data = json_decode($api_26_response['resp'], true);
                $customerIdSupple = $response_26_data['customerId'] ?? '';

                if(!empty($customerIdSupple)){
                    $api_27_response = self::update_credit_card_customer(
                        $file_info_id,
                        $result['applicant']->file_sl_no,
                        self::_prep_api_27_data($customerIdSupple, $supplementary)
                    );

                    $api_47_response = self::supplementary_card_creation(
                        $file_info_id,
                        $supplementary->file_sl_no,
                        $customerCardNo,
                        $supplementary->tz_product_id,
                        $customerIdSupple,
                        $customerContractNo
                    );

                    $response_data = json_decode($api_47_response['resp'], true);

                    $cardNumber = $response_data['cardNumber'] ?? '';

                    if(!empty($cardNumber)){

                        // [Pervez Bhai: Dec 10, 2025]: Applicant's accountNumber, Supples's cardNumber and customerId : Start =-
                        $api_84_response = self::addCardMobileNumber(
                            $file_info_id
                            , $supplementary->file_sl_no
                            , [
                                'mobile_no'      => $supplementary->mobile_no,
                                'account_number' => $accountNumber,
                                'pan'            => $cardNumber,
                                'client_id'      => $customerIdSupple,
                            ]
                        );
                        
                        if(!empty($supplementary->email)){
                            
                            $api_85_response = self::addCardEmailAdress(
                                $file_info_id
                                , $supplementary->file_sl_no
                                , [
                                    'email_address'  => $supplementary->email,
                                    'account_number' => $accountNumber,
                                    'pan'            => $cardNumber,
                                    'client_id'      => $customerIdSupple,
                                ]
                            );
                        }    
                        // [Pervez Bhai: Dec 10, 2025]: Applicant's accountNumber, Supples's cardNumber and customerId : End =-
                    }
                    DB::table('CANDIDATE_INFO')->where('ID', $supplementary->id)->update([
                        'CUSTOMER_ID_CMS'   => $customerIdSupple,
                        // 'CONTRACT_NO'       => $customerContractNo,
                        'CARD_NO_MASKED'    => !empty($cardNumber) ? maskCardNumber($cardNumber) : '',
                    ]);
                }
            }
        }

        return [
            'status' => 1,
            'message' => 'API Successed'
        ];
    }

    private static function _processLE($file_info_id, $result)
    {
        // $getEmptyFields = self::getEmptyFields($result);
        // if (!empty($getEmptyFields)) {
        //     return ['validation' => $getEmptyFields];
        // }

        $api_49_response = $api_46_response = [];

        $api_49_response = self::create_credit_card_contract_with_card(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $result['applicant']->tz_product_id,
            $result['applicant']->client_id
        );
        $arr = json_decode($api_49_response['resp'], true);
        $customerId = $result['applicant']->customer_id_cms ?? '';
        $customerContractNo = $arr['contractNumber'] ?? '';
        $customerCardNo = $arr['cardNumber'] ?? '';

        if (empty($customerContractNo) || empty($customerCardNo)) {
            return [
                'status' => 0,
                'message' => 'API Faied'
            ];
        }

        $api_46_response = self::set_credit_card_contract_limit(
            $file_info_id,
            $result['applicant']->file_sl_no,
            $customerContractNo,
            $customerCardNo,
            $result['applicant']->approved_amount
        );

        // DB::table('CANDIDATE_INFO')->where('ID', $result['applicant']->id)->update([
        //     'CUSTOMER_ID_CMS'   => $customerId,
        //     'CONTRACT_NO'       => $customerContractNo,
        // ]);

        return [
            'status' => 1,
            'message' => 'API Successed'
        ];
    }    

    private static function getEmptyFields($data)
    {
        $emptyFields = [];

        foreach ($data as $groupKey => $groupValue) {
            if (is_object($groupValue)) {
                $groupValue = (array) $groupValue;
            }

            $emptyFields[$groupKey] = [];

            if (!is_array($groupValue) || empty($groupValue)) {
                continue;
            }

            foreach ($groupValue as $key => $value) {
                if (is_object($value)) {
                    $value = (array) $value;
                }

                if (is_array($value)) {
                    foreach ($value as $subKey => $subVal) {
                        if ($subVal === null || $subVal === '' || $subVal === []) {
                            if (!isset($emptyFields[$groupKey][$key])) {
                                $emptyFields[$groupKey][$key] = [];
                            }
                            $emptyFields[$groupKey][$key][] = $subKey;
                        }
                    }
                } else {
                    if ($value === null || $value === '') {
                        $emptyFields[$groupKey][] = $key;
                    }
                }
            }

            if (empty($emptyFields[$groupKey])) {
                unset($emptyFields[$groupKey]);
            }
        }

        return $emptyFields;
    }

    public static function _prep_api_27_data($customerId, $item)
    {
        // Format registration address [Applicant Address Information company_name, business_name, designation_name, registration_address]
        // note : company_name and business_name are mutually exclusive
        $companyName     = $item->company_name ?? '';
        $businessName    = $item->business_name ?? '';
        $designationName = ($item->designation_name ?? '') == 'SELECT' ? '' : $item->designation_name;
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
            'customerId' => $customerId,
            'customerPhoneNo' => $item->phone_no,
            'customerEmail' => $item->email,
            'customerMobileNo' => $item->mobile_no,
            'customerFaxNo' => $item->fax_no,
            'fatherName' => $item->father_name,
            'motherName' => $item->mother_name,
            'spouse' => $item->spouse_name,
            'placeOfBirth' => $item->place_of_birth,
            'regCountry' => $item->registration_country_code,
            'regAddressRegion' => $item->registration_region_code,
            'regAddressCity' => $item->registration_city_code,
            'regZipCode' => $item->registration_postal_code,
            'regFullAddress' => $item->registration_address,
            'residenceCountry' => $item->residence_country_code,
            'residenceRegion' => $item->residence_region_code,
            'residenceCity' => $item->residence_city_code,
            'residenceZipCode' => $item->residence_postal_code,
            'residenceFullAddress' => $item->residence_address,
            'correspondenceCountry' => $item->registration_country_code,
            'correspondenceRegion' => $item->registration_region_code,
            'correspondenceCity' => $item->registration_city_code,
            'correspondenceZipCode' => $item->registration_postal_code,
            'correspondenceFullAddress' => $item->registration_address,
            'residentStatus' => $item->residential_status,
            'educationStatus' => $item->education_level,
            'maritalStatus' => $item->marital_status,
            'occupationStatus' => $item->occupation_code,
            // 'occupationStatus' => self::getOccupationCode($item->customer_segment_id),
            'customerNationality' => $item->nationality,
            'employeeId' => $item->staff_id,
            'company' => $item->company_name,
            'department' => $item->department_name,
            'position' =>  $item->product_generic_id == 6 ? $item->designation_name : '',
            'tin' => $item->etin,
            'nid' => $item->nid,
            'smartCardNo' => $item->smart_card,
            'passportNo' => $item->passport_no,
            'passportName' => $item->name_on_passport,
            'passportAddress' => $item->address_on_passport,
            'passportIssueDate' => $item->passport_issuing_date,
            'passportIssueExpiryDate' => $item->passport_expiry_date,
            'passportIssuePlace' => $item->passport_issue_place,
            'passportIssueCounty' => $item->passport_issuing_country_name=='Bangladesh' ? 'BD' : '',
            'applicationSource' => $item->application_source_id,
            'applicationReceiveDate' => $item->entry_date,
            'applicationAcceptDate' => $item->approve_date,
            'applicationNo' => "STP-".$item->file_sl_no,
            'rmCode' => $item->source_user_id,
            'documentIssueCountry' => (string) $item->document_issue_country_id,
            'collectionCallSensitiveCustomer' => (string) $item->ccs_customer_type_id ?? '',
            'taxReturnAssessmentYear' => self::normalizeYearRange($item->tax_return_assessment_year),
            'receiveCardThrough' => (string) $item->receive_card_through,
            'branchNameToReceiveCard' => (string) $item->br_id_to_receive_card,
        ];
        /*
        As discussed with Parvez Bhai (on 19 AUG 2025):
        --------------------------------------------------
        If the "Receive Card Through" option is set to Courier:
          - When the Address Type is "Present", select the "Present address" as the "correspondence address".
          - When the Address Type is "Office", select the "Office address" as the "correspondence address".
        For any value other than "Courier" selected in the "Receive Card Through" option, always use the "Office address" as the correspondence address.
        */
        if($item->receive_card_through == 1 
        && strtoupper($item->delivery_address_type_name) == 'PRESENT'){
            $data['correspondenceCountry'] = $item->residence_country_code;
            $data['correspondenceRegion'] = $item->residence_region_code;
            $data['correspondenceCity'] = $item->residence_city_code;
            $data['correspondenceZipCode'] = $item->residence_postal_code;
            $data['correspondenceFullAddress'] = $item->residence_address;
        }

        return $data;
    }

    public static function OLD___prep_api_27_data($customerId, $result)
    {
        // Format registration address [Applicant Address Information company_name, business_name, designation_name, registration_address]
        // note : company_name and business_name are mutually exclusive
        $companyName     = $result['applicant']->company_name ?? '';
        $businessName    = $result['applicant']->business_name ?? '';
        $designationName = ($result['applicant']->designation_name ?? '') == 'SELECT' ? '' : $result['applicant']->designation_name;
        $finalName       = $companyName ?: $businessName;
        $mainPart        = implode(', ', array_filter([$designationName, $finalName]));
        if (!empty($mainPart) && !empty($result['applicant']->registration_address)) {
        $fullText = $mainPart . ' * ' . $result['applicant']->registration_address;
        } else {
        $fullText = $mainPart ?: $result['applicant']->registration_address;
        }
        $result['applicant']->registration_address = $fullText;
        // End Format registration address

        $data = [
            'customerId' => $customerId,
            'customerPhoneNo' => $result['applicant']->phone_no,
            'customerEmail' => $result['applicant']->email,
            'customerMobileNo' => $result['applicant']->mobile_no,
            'customerFaxNo' => $result['applicant']->fax_no,
            'fatherName' => $result['applicant']->father_name,
            'motherName' => $result['applicant']->mother_name,
            'spouse' => $result['applicant']->spouse_name,
            'placeOfBirth' => $result['applicant']->place_of_birth,
            'regCountry' => $result['applicant']->registration_country_code,
            'regAddressRegion' => $result['applicant']->registration_region_code,
            'regAddressCity' => $result['applicant']->registration_city_code,
            'regZipCode' => $result['applicant']->registration_postal_code,
            'regFullAddress' => $result['applicant']->registration_address,
            'residenceCountry' => $result['applicant']->residence_country_code,
            'residenceRegion' => $result['applicant']->residence_region_code,
            'residenceCity' => $result['applicant']->residence_city_code,
            'residenceZipCode' => $result['applicant']->residence_postal_code,
            'residenceFullAddress' => $result['applicant']->residence_address,
            'correspondenceCountry' => $result['applicant']->registration_country_code,
            'correspondenceRegion' => $result['applicant']->registration_region_code,
            'correspondenceCity' => $result['applicant']->registration_city_code,
            'correspondenceZipCode' => $result['applicant']->registration_postal_code,
            'correspondenceFullAddress' => $result['applicant']->registration_address,
            'residentStatus' => $result['applicant']->residential_status,
            'educationStatus' => $result['applicant']->education_level,
            'maritalStatus' => $result['applicant']->marital_status,
            'occupationStatus' => $result['applicant']->occupation_code,
            // 'occupationStatus' => self::getOccupationCode($result['applicant']->customer_segment_id),
            'customerNationality' => $result['applicant']->nationality,
            'employeeId' => $result['applicant']->staff_id,
            'company' => $result['applicant']->company_name,
            'department' => $result['applicant']->department_name,
            'position' =>  $result['applicant']->product_generic_id == 6 ? $result['applicant']->designation_name : '',
            'tin' => $result['applicant']->etin,
            'nid' => $result['applicant']->nid,
            'smartCardNo' => $result['applicant']->smart_card,
            'passportNo' => $result['applicant']->passport_no,
            'passportName' => $result['applicant']->name_on_passport,
            'passportAddress' => $result['applicant']->address_on_passport,
            'passportIssueDate' => $result['applicant']->passport_issuing_date,
            'passportIssueExpiryDate' => $result['applicant']->passport_expiry_date,
            'passportIssuePlace' => $result['applicant']->passport_issue_place,
            'passportIssueCounty' => $result['applicant']->passport_issuing_country_name=='Bangladesh' ? 'BD' : '',
            'applicationSource' => $result['applicant']->application_source_id,
            'applicationReceiveDate' => $result['applicant']->entry_date,
            'applicationAcceptDate' => $result['applicant']->approve_date,
            'applicationNo' => $result['applicant']->file_sl_no,
            'rmCode' => $result['applicant']->source_user_id,
            'documentIssueCountry' => (string) $result['applicant']->document_issue_country_id,
            'collectionCallSensitiveCustomer' => (string) $result['applicant']->ccs_customer_type_id ?? '',
            'taxReturnAssessmentYear' => self::normalizeYearRange($result['applicant']->tax_return_assessment_year),
            'receiveCardThrough' => (string) $result['applicant']->receive_card_through,
            'branchNameToReceiveCard' => (string) $result['applicant']->br_id_to_receive_card,
        ];
        /*
        As discussed with Parvez Bhai (on 19 AUG 2025):
        --------------------------------------------------
        If the "Receive Card Through" option is set to Courier:
          - When the Address Type is "Present", select the "Present address" as the "correspondence address".
          - When the Address Type is "Office", select the "Office address" as the "correspondence address".
        For any value other than "Courier" selected in the "Receive Card Through" option, always use the "Office address" as the correspondence address.
        */
        if($result['applicant']->receive_card_through == 1 
        && strtoupper($result['applicant']->delivery_address_type_name) == 'PRESENT'){
            $data['correspondenceCountry'] = $result['applicant']->residence_country_code;
            $data['correspondenceRegion'] = $result['applicant']->residence_region_code;
            $data['correspondenceCity'] = $result['applicant']->residence_city_code;
            $data['correspondenceZipCode'] = $result['applicant']->residence_postal_code;
            $data['correspondenceFullAddress'] = $result['applicant']->residence_address;
        }

        return $data;
    }

    private static function _prep_api_29_data($customerContractNo, $result)
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

    // collection_sensitive_customer

    public static function prepareCardAPIData($file_info_id)
    {
        $data = ['applicant' => [], 'supplementary' => [],];

        // Before processing, confirm that Approved amount is available in FILE_INFO table
        $sql = "MERGE INTO FILE_INFO A
            USING FILE_ASSESMENT_INFO B ON (A.ID = B.FILE_INFO_ID)
            WHEN MATCHED THEN
            UPDATE SET
                A.APPROVED_AMOUNT = B.RECOMMENDED_AMOUNT
            WHERE
                A.ID = :p0";
        DB::statement($sql, ['p0' => $file_info_id]);

        // Start processing
        $cand_ttls = config('card_api.customerTitle');
        $cand_ttl_sql = "CASE C.CANDIDATE_TITLE ";
        foreach ($cand_ttls as $key => $value) {
            $cand_ttl_sql .= "WHEN N'$value' THEN $key ";
        }
        $cand_ttl_sql .= "ELSE 0 END AS CANDIDATE_TITLE";

        $sql = "SELECT DISTINCT C.ID, F.ID AS FILE_ID, F.FILE_SL_NO, C.CANDIDATE_TYPE, F.CLIENT_ID, C.CUSTOMER_ID_CMS, C.CONTRACT_NO,
                FAI.RECOMMENDED_AMOUNT AS APPROVED_AMOUNT, F.CARD_FACILITY_TYPE,
                CASE F.CARD_FACILITY_TYPE WHEN N'Limit Enhancement (LE)' THEN F.EXISTING_FILE_SL_NO ELSE F.FILE_SL_NO END AS EXISTING_FILE_SL_NO_CALC,
                " . $cand_ttl_sql . ",
                C.CANDIDATE_NAME, C.NAME_TRANSLITERATION, CASE C.GENDER WHEN N'Male' THEN 'M' WHEN N'Female' THEN 'F' ELSE 'U' END AS GENDER,
                TO_CHAR(C.DOB, 'DD/MM/YYYY') AS DOB,
                TO_CHAR(F.SOURCE_SUBMIT_DT, 'DD/MM/YYYY') AS ENTRY_DATE,
                TO_CHAR(F.APPROVER_SUBMIT_DT, 'DD/MM/YYYY') AS APPROVE_DATE,
                TO_CHAR(CCI.GIFT_ENTRY_DATE, 'DD/MM/YYYY') AS GIFT_ENTRY_DATE,
                C.FATHER_NAME, C.MOTHER_NAME, C.SPOUSE_NAME, C.DISTRICT_BIRTH_NAME AS PLACE_OF_BIRTH,
                C.RESIDENTIAL_STATUS, C.EDUCATION_LEVEL_NAME AS EDUCATION_LEVEL, C.MARITAL_STATUS,
                CASE WHEN C.NATIONALITY_COUNTRY_ID = " . config('card_api.NationalityBDCountryId') . " THEN '1' ELSE '2' END AS NATIONALITY,
                C.STAFF_ID, C.COMPANY_ID, C.COMPANY_NAME, C.DEPARTMENT_NAME, P.DESIGNATION_NAME,
                C.ETIN, C.NID, C.SMART_CARD, C.PASSPORT_NO, C.NAME_ON_PASSPORT, C.ADDRESS_ON_PASSPORT, 
                C.EMAIL, C.MOBILE_NO, C.PHONE_NO, C.FAX_NO, C.TAX_RETURN_ASSESSMENT_YEAR,
                TO_CHAR(C.PASSPORT_ISSUING_DATE, 'DD/MM/YYYY') AS PASSPORT_ISSUING_DATE, TO_CHAR(C.PASSPORT_EXPIRY_DATE, 'DD/MM/YYYY') AS PASSPORT_EXPIRY_DATE, C.PASSPORT_ISSUE_PLACE, C.PASSPORT_ISSUING_COUNTRY_NAME,
                F.SOURCE_USER_ID, C.DOCUMENT_ISSUE_COUNTRY_ID, C.CCS_CUSTOMER_TYPE_ID,
                C.TAX_RETURN_ASSESSMENT_YEAR, C.RECEIVE_CARD_THROUGH, C.DELIVERY_ADDRESS_TYPE_NAME, C.BR_ID_TO_RECEIVE_CARD,
                ADR.*, RF.*, F.PRODUCT_ID,F.PRODUCT_GENERIC_ID,
                CCI.OCCUPATION_STATUS_ID, C.APPLICATION_SOURCE_ID,
                CCI.THIRD_PARTY_AGENT, CCI.AUTO_DEBIT_VALUE2 AS AUTO_DEBIT_EFTN_INSTRUCTION,
                CCI.CITY_SHIELD, CCI.SECURITY_LIEN_INFO, CCI.COLLATERAL_VALUE_LOCAL, CCI.COLLATERAL_VALUE_FOREIGN,
                CCI.NAME_OF_FIRST_NOMINEE, CCI.FIRST_NOMINEE_PERCENTAGE, CCI.NAME_OF_SECOND_NOMINEE, CCI.SECOND_NOMINEE_PERCENTAGE,
                FAI.RECOMMENDED_DBR, F.APPROVER_ALLOC_USER_NAME AS APPROVER_NAME,
                CCI.CORPORATE_FIELD_MAPPING, CCI.LIABILITY_TYPE,
                CCI.BILLING_PAYMENT_OPTION, CCI.RECEIVE_STATEMENT_THROUGH, CCI.CASA_ACCOUNT_SCHEME, CCI.WELCOME_GIFT,
                CCI.CAMPAIGN_ID, CCI.REQUIRED_CHEQUE_BOOK, CCI.SPECIAL_REMARKS, CCI.TZ_PRODUCT_ID,
                C.CARD_FEES_PROFILE, F.IS_LE_CARD, F.CUSTOMER_SEGMENT_ID, NOP.SECTOR_CODE AS OCCUPATION_CODE,C.BUSINESS_NAME
            FROM FILE_INFO F
            INNER JOIN CANDIDATE_INFO C ON C.FILE_INFO_ID = F.ID AND C.DATA_STATUS = 1
            LEFT JOIN CANDIDATE_PROFESSIONAL_INFO P ON P.CANDIDATE_INFO_ID = C.ID
            LEFT JOIN REF_NATURE_OF_PROFESSION NOP ON NOP.ID = C.NATURE_OF_PROFESSION_ID
            LEFT JOIN CARD_CONTRACT_INFO CCI ON CCI.FILE_INFO_ID = F.ID
            LEFT JOIN REFERENCE_INFO R ON R.FILE_INFO_ID = F.ID AND R.DATA_STATUS = 1
            LEFT JOIN FILE_ASSESMENT_INFO FAI ON FAI.FILE_INFO_ID = F.ID
            LEFT JOIN (
                SELECT
                    FILE_INFO_ID,
                    CANDIDATE_INFO_ID,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Present' THEN COUNTRY_CODE END) AS RESIDENCE_COUNTRY_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Present' THEN REGION_CODE END) AS RESIDENCE_REGION_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Present' THEN CITY_CODE END) AS RESIDENCE_CITY_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Present' THEN ADDRESS_DETAILS END) AS RESIDENCE_ADDRESS,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Present' THEN
                        TRIM(SUBSTR(POSTAL_CODE_NAME, INSTR(POSTAL_CODE_NAME, '-', -1) + 1))
                    END) AS RESIDENCE_POSTAL_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Office' THEN COUNTRY_CODE END) AS REGISTRATION_COUNTRY_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Office' THEN REGION_CODE END) AS REGISTRATION_REGION_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Office' THEN CITY_CODE END) AS REGISTRATION_CITY_CODE,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Office' THEN ADDRESS_DETAILS END) AS REGISTRATION_ADDRESS,
                    MAX(CASE WHEN ADDRESS_TYPE = N'Office' THEN
                        TRIM(SUBSTR(POSTAL_CODE_NAME, INSTR(POSTAL_CODE_NAME, '-', -1) + 1))
                    END) AS REGISTRATION_POSTAL_CODE,
                    MAX(CASE WHEN IS_COMMUNICATION_ADDRESS = 1 THEN COUNTRY_CODE END) AS CORRESPONDENCE_COUNTRY_CODE,
                    MAX(CASE WHEN IS_COMMUNICATION_ADDRESS = 1 THEN REGION_CODE END) AS CORRESPONDENCE_REGION_CODE,
                    MAX(CASE WHEN IS_COMMUNICATION_ADDRESS = 1 THEN CITY_CODE END) AS CORRESPONDENCE_CITY_CODE,
                    MAX(CASE WHEN IS_COMMUNICATION_ADDRESS = 1 THEN ADDRESS_DETAILS END) AS CORRESPONDENCE_ADDRESS,
                    MAX(CASE WHEN IS_COMMUNICATION_ADDRESS = 1 THEN
                        TRIM(SUBSTR(POSTAL_CODE_NAME, INSTR(POSTAL_CODE_NAME, '-', -1) + 1))
                    END) AS CORRESPONDENCE_POSTAL_CODE
                FROM CANDIDATE_ADDRESS
                WHERE DATA_STATUS = 1
                GROUP BY FILE_INFO_ID, CANDIDATE_INFO_ID
            ) ADR ON ADR.CANDIDATE_INFO_ID = C.ID
            LEFT JOIN (
                SELECT 
                    FILE_INFO_ID,
                    MAX(CASE WHEN rn = 1 THEN REFERENCE_NAME END) AS REFERENCE_NAME,
                    MAX(CASE WHEN rn = 1 THEN ADDRESS END) AS REFERENCE_ADDRESS,
                    MAX(CASE WHEN rn = 1 THEN MOBILE_NO END) AS REFERENCE_PHONE_NUMBER,
                    MAX(CASE WHEN rn = 2 THEN REFERENCE_NAME END) AS SECOND_REFERENCE_NAME,
                    MAX(CASE WHEN rn = 2 THEN ADDRESS END) AS SECOND_REFERENCE_ADDRESS,
                    MAX(CASE WHEN rn = 2 THEN MOBILE_NO END) AS SECOND_REFERENCE_PHONE_NUMBER
                FROM (
                    SELECT FILE_INFO_ID, ID, REFERENCE_NAME, ADDRESS, MOBILE_NO,
                        ROW_NUMBER() OVER (PARTITION BY FILE_INFO_ID ORDER BY ID ASC) AS rn
                    FROM REFERENCE_INFO
                    WHERE FILE_INFO_ID = :file_id AND DATA_STATUS = 1
                ) RF_GROUP
                WHERE rn <= 2
                GROUP BY FILE_INFO_ID
            ) RF ON RF.FILE_INFO_ID = F.ID
            WHERE C.FILE_INFO_ID = :file_id
            ORDER BY C.ID ASC";

        $result = DB::select($sql, ['file_id' => $file_info_id]);

        foreach ($result as $item) {
            $item->residential_status = self::_getAPIMappedValue($item->residential_status, 'system.residential_status', 'card_api.residentStatusMapped', '4');
            $item->education_level = self::_getAPIMappedValue($item->education_level, 'system.education_level', 'card_api.educationStatusMapped');
            $item->marital_status = self::_getAPIMappedValue($item->marital_status, 'system.marital_status', 'card_api.maritalStatusMapped');
            // $item->occupation_code = self::getOccupationCode($item->customer_segment_id);

            // Replace NULL values with empty string
            foreach ($item as $key => $value) {
                $item->$key = $value ?? '';
            }

            // Segregating by candidate_type
            if ($item->candidate_type === 'Applicant') {
                $data['applicant'] = $item;
            } else if ($item->candidate_type === 'Supplementary') {
                $data['supplementary'][$item->id] = $item;
            }
        }

        if (count($data['supplementary']) > 0) {
            foreach ($data['supplementary'] as $key => $supplementary) {
                $data['supplementary'][$key]->application_source_id = $data['applicant']->application_source_id;
            }
        }
        
        return CommonService::convertToUpperCase($data);
    }

    public static function getOccupationCode($customer_segment_id)
    {
        $sectorCode = DB::table('REF_CUSTOMER_SEGMENT as A')
            ->join('REF_SECTOR_CODE as B', 'B.ID', '=', 'A.SECTOR_CODE')
            ->where('A.ID', $customer_segment_id)
            ->value('B.SECTOR_CODE');

        return $sectorCode ?? '';
    }

    private static function _getAPIMappedValue($sts, $system_field, $mapped_field, $dafault_value = '')
    {
        $system_sts = config($system_field);
        $api_sts = config($mapped_field);
        $key = array_search($sts, $system_sts);

        return $key !== false && isset($api_sts[$key]['cms_code'])
            ? $api_sts[$key]['cms_code']
            : $dafault_value;
    }

    private static function _prepRequestJson($fields)
    {
        $fields = array_merge($fields, [
            "channelName" => 'TEST',
            "channelSecret" => 'TEST',
            "channelTransactionId" => "" . time(),
        ]);

        return json_encode($fields, true);
    }

    private static function _loadCreateClientCreditCardResponse()
    {
        return [
            "contractNo" => "100000086313",
            "customerId" => "2321143",
            "pan" => "376948112410739",
            "channelTransactionId" => "1738577766",
            "serviceId" => "0c3f72b7-4e25-4b39-bd48-71198250bccc",
            "timestamp" => "03-02-2025 16:16:06",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "response_time" => "0",
            "api_name" => "createClientCreditCard",
        ];
    }

    // (28) createClientCreditCard
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
        $contract_type_id
    ) {
        $api_id = "28";
        $apiResponse = [];

        $post_fields = [
            "customerTitle" => $applicant_title,
            "customerName" => cleanName($applicant_name),
            "customerTransName" => cleanName($name_transliteration),
            "gender" => $gender,
            "dob" => tranzwireDateFormat($dob, 'd/m/Y'),
            "documentMaskingBy" => (string) $doc_masking_key,
            "documentMaskingNumber" => (string) $doc_masking_no,
            "contractTypeId" => (string) $contract_type_id,
        ];
        
        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadCreateClientCreditCardResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadSetCreditCardContractLimitResponse()
    {
        return [
            "channelTransactionId" => "ashiq-1738578212",
            "serviceId" => "13d5e88f-294f-484a-a1d8-d10984d6745f",
            "timestamp" => "03-02-2025 16:23:32",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "response_time" => "0",
            "api_name" => "setCreditCardContractLimit",
        ];
    }

    // (46) setCreditCardContractLimit
    public static function set_credit_card_contract_limit($fileId, $fileNo, $contractId, $cardNumber, $amount)
    {
        $api_id = "46";
        $apiResponse = [];
        $post_fields = [
            "contractId" => $contractId,
            "cardNumber" => $cardNumber,
            "amount" => $amount,
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadSetCreditCardContractLimitResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadUpdateCreditCardCustomerResponse()
    {
        return [
            "channelTransactionId" => "1738604743",
            "serviceId" => "918c9d7c-00c9-4636-ad04-18cae5187218",
            "timestamp" => "03-02-2025 23:45:44",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "response_time" => "0",
            "api_name" => "updateCreditCardCustomer",
        ];
    }

    // (27) updateCreditCardCustomer
    public static function update_credit_card_customer($fileId, $fileNo, $card_info)
    {
        $api_id = "27";
        $apiResponse = [];
        $post_fields = [
            'customerId' => $card_info['customerId'] ?? '',
            'customerPhoneNo' => $card_info['customerPhoneNo'] ?? '',
            'customerEmail' => $card_info['customerEmail'] ?? '',
            'customerMobileNo' => isset($card_info['customerMobileNo']) ? tranzwireMobileNumberFormat($card_info['customerMobileNo']) : '',
            'customerFaxNo' => '', // FIXED VALUE
            'fatherName' => cleanName($card_info['fatherName']) ?? '',
            'motherName' => cleanName($card_info['motherName']) ?? '',
            'spouse' => cleanName($card_info['spouse']) ?? '',
            'placeOfBirth' => $card_info['placeOfBirth'] ?? '',
            'regCountry' => !empty($card_info['regFullAddress']) ? '50' : '',
            'regAddressRegion' => $card_info['regAddressRegion'] ?? '',
            'regAddressCity' => $card_info['regAddressCity'] ?? '',
            'regZipCode' => $card_info['regZipCode'] ?? '',
            'regFullAddress' => $card_info['regFullAddress'] ?? '',
            'residenceCountry' => !empty($card_info['residenceFullAddress']) ? '50' : '',
            'residenceRegion' => $card_info['residenceRegion'] ?? '',
            'residenceCity' => $card_info['residenceCity'] ?? '',
            'residenceZipCode' => $card_info['residenceZipCode'] ?? '',
            'residenceFullAddress' => $card_info['residenceFullAddress'] ?? '',
            'correspondenceCountry' => !empty($card_info['correspondenceFullAddress']) ? '50' : '',
            'correspondenceRegion' => $card_info['correspondenceRegion'] ?? '',
            'correspondenceCity' => $card_info['correspondenceCity'] ?? '',
            'correspondenceZipCode' => $card_info['correspondenceZipCode'] ?? '',
            'correspondenceFullAddress' => $card_info['correspondenceFullAddress'] ?? '',
            'residentStatus' => $card_info['residentStatus'] ?? '',
            'educationStatus' => $card_info['educationStatus'] ?? '',
            'maritalStatus' => $card_info['maritalStatus'] ?? '',
            'occupationStatus' => $card_info['occupationStatus'] ?? '',
            'customerNationality' => $card_info['customerNationality'] ?? '',
            'employeeId' => $card_info['employeeId'] ?? '',
            // 'company' => $card_info['company'] ?? '',
            'company' => '', // FIXED VALUE
            // 'department' => $card_info['department'] ?? '',
            'department' => '', // FIXED VALUE
            'position' => $card_info['position'] ?? '',
            'tin' => $card_info['tin'] ?? '',
            'nid' => $card_info['nid'] ?? '',
            'smartCardNo' => $card_info['smartCardNo'] ?? '',
            'passportNo' => $card_info['passportNo'] ?? '',
            'passportName' => $card_info['passportName'] ?? '',
            'passportAddress' => $card_info['passportAddress'] ?? '',
            'passportIssueDate' => tranzwireDateFormat($card_info['passportIssueDate'], 'd/m/Y'),
            'passportIssueExpiryDate' => tranzwireDateFormat($card_info['passportIssueExpiryDate'], 'd/m/Y'),
            'passportIssuePlace' => $card_info['passportIssuePlace'] ?? '',
            'passportIssueCounty' => $card_info['passportIssueCounty'] ?? '',
            'applicationSource' => $card_info['applicationSource'] ?? '',
            'applicationReceiveDate' => tranzwireDateFormat($card_info['applicationReceiveDate'], 'd/m/Y'),
            'applicationAcceptDate' => tranzwireDateFormat($card_info['applicationAcceptDate'], 'd/m/Y'),
            'applicationNo' => $card_info['applicationNo'] ?? '',
            'rmCode' => $card_info['rmCode'] ?? '',
            'documentIssueCountry' => $card_info['documentIssueCountry'] ?? '',
            'collectionCallSensitiveCustomer' => $card_info['collectionCallSensitiveCustomer'] ?? '',
            'taxReturnAssessmentYear' => self::normalizeYearRange($card_info['taxReturnAssessmentYear']),
            'receiveCardThrough' => $card_info['receiveCardThrough'] ?? '',
            'branchNameToReceiveCard' => $card_info['branchNameToReceiveCard'] ?? '',
        ];
        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadUpdateCreditCardCustomerResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadUpdateCreditCardContractResponse()
    {
        return [
            "channelTransactionId" => "ashiq-1738140588",
            "serviceId" => "ed0e9ef4-35ad-4a31-a219-7f98168fa76b",
            "timestamp" => "29-01-2025 14:48:53",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "response_time" => "0",
            "api_name" => "updateCreditCardContract",
        ];
    }

    private static function _loadUpdateCreditCardResponse()
    {
        return [
            "channelTransactionId" => "ashiq-1738140588",
            "serviceId" => "ed0e9ef4-35ad-4a31-a219-7f98168fa76b",
            "timestamp" => "29-01-2025 14:48:53",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "response_time" => "0",
            "api_name" => "updateCreditCardContract",
        ];
    }

    // (29) updateCreditCardContract
    public static function update_credit_card_contract($fileId, $fileNo, $card_info)
    {
        $api_id = "29";
        $apiResponse = [];
        $post_fields = [
            'contractNumber' => $card_info['contractNumber'] ?? '',
            'RFCApproveLimit' => $card_info['RFCApproveLimit'] ?? '',
            'thirdPartyAgent' => $card_info['thirdPartyAgent'] ?? '',
            'autoDebitEFTNInstruction' => $card_info['autoDebitEFTNInstruction'] ?? '',
            'cityShield' => $card_info['cityShield'] ?? '',
            'securityLIENInfo' => $card_info['securityLIENInfo'] ?? '',
            'receiveCardFrom' => $card_info['receiveCardFrom'] ?? '',
            'receiveBranch' => $card_info['receiveBranch'] ?? '',
            'applicationFileNoCACV' => "STP-". $card_info['applicationFileNoCACV'] ?? '',
            'collateralValueBDT' => $card_info['collateralValueBDT'] ?? '',
            'collateralValueUSD' => $card_info['collateralValueUSD'] ?? '',
            'applicationFileNoLE' => $card_info['applicationFileNoLE'] ?? '',
            'referenceName' => $card_info['referenceName'] ?? '',
            'refereceAddress' => $card_info['refereceAddress'] ?? '',
            'referencePhoneNumber' => $card_info['referencePhoneNumber'] ?? '',
            'secondReferenceName' => $card_info['secondReferenceName'] ?? '',
            'secondReferenceAddress' => $card_info['secondReferenceAddress'] ?? '',
            'secondReferencePhoneNumber' => $card_info['secondReferencePhoneNumber'] ?? '',
            'nameOfFirstNominee' => $card_info['nameOfFirstNominee'] ?? '',
            'firstNomineePercentage' => $card_info['firstNomineePercentage'] ?? '',
            'nameOfSecondNominee' => $card_info['nameOfSecondNominee'] ?? '',
            'secondNomineePercentage' => $card_info['secondNomineePercentage'] ?? '',
            'dBRRange' => isset($card_info['dBRRange']) && $card_info['dBRRange'] > 0 ? $card_info['dBRRange'].'%' : '',
            'approverName' => $card_info['approverName'] ?? '',
            'corporateName' => $card_info['corporateName'] ?? '',
            'liabilityType' => $card_info['liabilityType'] ?? '',
            'billingPaymentOption' => $card_info['billingPaymentOption'] ?? '',
            'receiveStatementThrough' => $card_info['receiveStatementThrough'] ?? '',
            'cASAAccountScheme' => $card_info['cASAAccountScheme'] ?? '',
            'welcomeGift' => $card_info['welcomeGift'] ?? '',
            'giftEntryDate' => tranzwireDateFormat($card_info['giftEntryDate'], 'd/m/Y'),
            'campaign' => $card_info['campaign'] ?? '',
            'requiredChequeBook' => $card_info['requiredChequeBook'] ?? '',
            'specialRemarks' => "0",  // Fixed Value
            'finProfile' => $card_info['finProfile'],
            'rmCode' => $card_info['rmCode'],
            'applicationSource' => $card_info['applicationSource'],
            'applicationAcceptDate' => tranzwireDateFormat($card_info['applicationAcceptDate'], 'd/m/Y'),
            'applicationReceiveDate' => '',
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadUpdateCreditCardContractResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    // (56) updateCreditCard
    public static function update_credit_card($fileId, $fileNo, $pan, $sourceCode, $finProfile)
    {
        $api_id = "56";
        $apiResponse = [];
        $post_fields = [
            'cardNo' => $pan ?? '',
            'rmCode' => $sourceCode ?? '',
            'applicationCode' =>'',
            'applicationSource' =>'',
            'cardSourceChannel' =>'',
            'finProfile' => $finProfile,
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadUpdateCreditCardResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadCreateCreditCardCustomerResponse()
    {
        return [
            "channelTransactionId" => "1738054505",
            "customerId" => "2321131",
            "serviceId" => "250281454003",
            "responseMessage" => "Operation Successful.",
            "timestamp" => "28-01-2025 14:54:09",
            "responseCode" => "100",
            "response_time" => "0",
            "api_name" => "createCreditCardCustomer",
        ];
    }

    // (26) createCreditCardCustomer
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
        $motherName
    ) {
        $api_id = "26";
        $apiResponse = [];
        $post_fields = [
            "customerTitle" => $customerTitle,
            "customerName" => cleanName($customerName),
            "customerTransName" => cleanName($customerTransName),
            "customerType" => $customerType,
            "gender" => $gender,
            "dob" => tranzwireDateFormat($dob, 'd/m/Y'),
            "documentMaskingBy" => $documentMaskingBy,
            "documentMaskingNumber" => $documentMaskingNumber,
            "fatherName" => cleanName($fatherName),
            "motherName" => cleanName($motherName),
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadCreateCreditCardCustomerResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadSupplymentaryCardCreationResponse()
    {
        return [
            "channelTransactionId" => "ashiq-1738056540",
            "serviceId" => "bde65b2e-ecad-4b00-b950-9acb228bf389",
            "timestamp" => "28-01-2025 15:28:04",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "cardNumber" => "371599209564964",
            "response_time" => "0",
            "api_name" => "supplymentaryCardCreation",
        ];
    }

    // 47. supplymentaryCardCreation
    public static function supplementary_card_creation($fileId, $fileNo, $parentPan, $productId, $customerIdSupple, $contractIdPrimary)
    {
        $api_id = "47";
        $apiResponse = [];
        $post_fields = [
            "parentPan" => $parentPan,
            "productId" => $productId,
            "customerId" => $customerIdSupple,
            "contractId" => $contractIdPrimary,
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadSupplymentaryCardCreationResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadCreateCreditCardContractWithAccountResponse()
    {
        // SUCCESS RESPONSE
        return [
            "contractNo" => "100000086297",
            "bdtAccountNo" => "1000500336028",
            "usdAccountNo" => null,
            "channelTransactionId" => "12323",
            "serviceId" => "250261508006",
            "timestamp" => "26-01-2025 15:08:53",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "response_time" => "0",
            "api_name" => "createCreditCardContractWithAccount",
        ];

        // FAILURE RESPONSE
        // return [
        //     "contractNo" => null,
        //     "bdtAccountNo" => null,
        //     "usdAccountNo" => null,
        //     "channelTransactionId" => "1738057068",
        //     "serviceId" => "250281536004",
        //     "timestamp" => "28-01-2025 15:36:52",
        //     "responseCode" => "000",
        //     "responseMessage" => "Operation Not Successful.",
        //     "response_time" => "0",
        //     "api_name" => "createCreditCardContractWithAccount",
        // ];
    }

    // 48. createCreditCardContractWithAccount
    public static function create_credit_card_contract_with_account($fileId, $fileNo, $contractTypeId, $clientId)
    {
        $api_id = "48";
        $apiResponse = [];
        $post_fields = [
            "contractTypeId" => $contractTypeId,
            "clientId" => $clientId,
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadCreateCreditCardContractWithAccountResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }

    private static function _loadCreateCreditCardContractWithCardResponse()
    {
        // SUCCESS RESPONSE
        return [
            "contractNumber" => "100000086332",
            "cardNumber" => "376948112144114",
            "channelTransactionId" => "1739697781",
            "serviceId" => "d6573565-d826-49ec-93af-2e023772719b",
            "timestamp" => "16-02-2025 15:23:01",
            "responseCode" => "100",
            "responseMessage" => "Operation Successful.",
            "api_name" => "createCreditCardContractWithCard",
        ];

        // FAILURE RESPONSE
        // return [
        //     "channelTransactionId" => "1739698162",
        //     "serviceId" => "e2c919a8-9a37-4cdf-9207-dbad64e2de82",
        //     "timestamp" => "16-02-2025 15:29:23",
        //     "responseCode" => "000",
        //     "responseMessage" => "Operation Not Successful.",
        //     "api_name" => "createCreditCardContractWithCard",
        // ];
    }

    // 49. createCreditCardContractWithCard
    public static function create_credit_card_contract_with_card($fileId, $fileNo, $contractTypeId, $clientId)
    {
        $api_id = "49";
        $apiResponse = [];
        $post_fields = [
            "contractTypeId" => $contractTypeId,
            "clientId" => $clientId,
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadCreateCreditCardContractWithCardResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }
    
    // 65. getCardCustomerDupeCheck
    public static function cleintIdByTin($fileId, $fileNo, $tin)
    {
        $api_id = "65";
        $apiResponse = [];
        $post_fields = [
            "searchBy"              => "TIN",
            "searchValue"           => $tin,
            "channelTransactionId"  => 'LOS' . date('ymd') . substr($fileNo, -7) . generateRandomDigits(5),
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadCleintIdByTinResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }
    
    private static function _loadCleintIdByTinResponse()
    {
        return array (
            'responseCode' => '100',
            'responseMessage' => 'Operation Successful.',
            'channelTransactionId' => '203424356tyKE3425466cESW',
            'serviceId' => '251950913018',
            'timeStamp' => '14-07-2025 09:13:26',
            'responseData' => 
            array (
                0 => array (
                    'clientId' => '980230',
                    'cardNo' => '371598XXXXX4531',
                    'customerName' => 'MD ASADUL HAQUE SARKER',
                    'cardStatus' => 'Declared',
                    'cardState' => 'Embossing',
                    'expiryDate' => '0329',
                ),
                1 => array (
                    'clientId' => '980230',
                    'cardNo' => '474867XXXXXX7791',
                    'customerName' => 'MD ASADUL HAQUE SARKER',
                    'cardStatus' => 'Open',
                    'cardState' => 'Given',
                    'expiryDate' => '0225',
                ),
            ),
        );
    }

    // 73. addCardMemo
    public static function addCardMemo($fileId, $fileNo, $cardNo)
    {
        $api_id = "73";
        $apiResponse = [];

        $post_fields = [
            "cardNo"                => $cardNo,
            "memoText"              => self::getMemoText($fileId, $fileNo),
            "channelTransactionId"  => ApiRequestService::makeChannelTransactionId($fileNo),
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point' => $api_config->api_url ?? '',
                'api_name' => $api_config->name ?? '',
                'req' => self::_prepRequestJson($post_fields),
                'resp' => json_encode(self::_loadAddCardMemoResponse()),
                'response_time' => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }
    
    private static function _loadAddCardMemoResponse()
    {
        return array (
            'serviceId'             => '252860758292',
            'timeStamp'             => '13-10-2025 07:58:53',
            'responseCode'          => '100',
            'responseMessage'       => 'Operation Successful.',
            'channelTransactionId'  => 'LOS251013000091610561',
            'errorMessage'          => NULL,
        );
    }

    // 84. addCardMobileNumber
    public static function addCardMobileNumber($fileId, $fileNo, $data)
    {
        $api_id = "84";
        $apiResponse = [];
        $post_fields = [
            'mobileNo'              => $data['mobile_no'], //'01303021359',
            'accountNumber'         => $data['account_number'], // '',
            'pan'                   => $data['pan'], // '371599207576333',
            'clientId'              => $data['client_id'], // '2320771',
            "channelTransactionId"  => 'LOS' . date('ymd') . substr($fileNo, -7) . generateRandomDigits(5),
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point'         => $api_config->api_url ?? '',
                'api_name'          => $api_config->name ?? '',
                'req'               => self::_prepRequestJson($post_fields),
                'resp'              => json_encode(self::_loadAddCardMobileNumber()),
                'response_time'     => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }
    
    private static function _loadAddCardMobileNumber()
    {
        return [
            'serviceId'             => '252961326543',
            'responseCode'          => '100',
            'responseMessage'       => 'Operation Successful.',
            'channelTransactionId'  => '01728113328',
            'timeStamp'             => '23-10-2025 13:26:44',            
        ];
    }

    // 85. addCardEmailAdress
    public static function addCardEmailAdress($fileId, $fileNo, $data)
    {
        $api_id = "85";
        $apiResponse = [];
        $post_fields = [
            'emailAddress'          => $data['email_address'], //'shajal@gmail.com',
            'accountNumber'         => $data['account_number'], // '',
            'pan'                   => $data['pan'], // '371599207576333',
            'clientId'              => $data['client_id'], // '2320771',
            "channelTransactionId"  => 'LOS' . date('ymd') . substr($fileNo, -7) . generateRandomDigits(5),
        ];

        if (config("system.api_env") == 2) {
            $api_config = collect(Cache::get('cache_api_list', []))->firstWhere('id', $api_id);
            $apiResponse = [
                'end_point'         => $api_config->api_url ?? '',
                'api_name'          => $api_config->name ?? '',
                'req'               => self::_prepRequestJson($post_fields),
                'resp'              => json_encode(self::_loadAddCardEmailAdress()),
                'response_time'     => '',
            ];
        } else {
            $apiResponse = ApiRequestService::processApiRequest($api_id, $post_fields);
        }

        ApiRequestService::saveApiLog($fileId, $fileNo, $api_id, $apiResponse);

        return $apiResponse;
    }
    
    private static function _loadAddCardEmailAdress()
    {
        return [
            'serviceId'             => '252961327544',
            'responseCode'          => '100',
            'responseMessage'       => 'Operation Successful.',
            'channelTransactionId'  => '017e28q22328',
            'timeStamp'             => '23-10-2025 13:27:31',           
        ];
    }
    
    public static function getSuppleProductId($product_id, $tz_product_id)
    {
        return DB::table('REF_CONTRACT_TYPE_OF_CMS')
            ->where('PRODUCT', $product_id)
            ->where('CODE', $tz_product_id)
            ->value('SUPPLE_CODE');
    }
    
    public static function normalizeYearRange(string $input): string
    {
        $parts = explode('-', $input);

        if (count($parts) !== 2) {
            return $input;
        }

        [$start, $end] = $parts;

        // Convert 2-digit to 4-digit assuming 2000s
        $start = strlen($start) === 2 ? '20' . $start : $start;
        $End =- strlen($end) === 2 ? '20' . $end : $end;

        return $start . '-' . $end;
    }

    public static function getMemoText($fileId, $fileNo)
    {
        $row = DB::table('FILE_INFO AS F')
            ->leftJoin('USERS_INFO AS CDE', 'CDE.ID', '=', 'F.CDE_LOCKED_BY')
            ->leftJoin('USERS_INFO AS DOC', 'DOC.ID', '=', 'F.DOC_LOCKED_BY')
            ->where('F.ID', $fileId)
            ->select([
                'F.ID AS FILE_ID',
                'CDE.USER_LOG_ID AS ENTRY_USER_LOG_ID',
                'CDE.USER_NAME   AS ENTRY_USER_NAME',
                'DOC.USER_LOG_ID AS DOC_USER_LOG_ID',
                'DOC.USER_NAME   AS DOC_USER_NAME',
            ])
            ->first();

        if (!$row) {
            return "LOS|File not found for ID: {$$fileNo}";
        }

        $entryUserLogId = $row->entry_user_log_id ?? 'N/A';
        $entryUserName  = $row->entry_user_name   ?? 'Unknown';
        $docUserLogId   = $row->doc_user_log_id   ?? 'N/A';
        $docUserName    = $row->doc_user_name     ?? 'Unknown';

        return sprintf(
            'LOS|Card Data Entry: %s, %s|Card Documentation: %s, %s',
            $entryUserLogId,
            $entryUserName,
            $docUserLogId,
            $docUserName
        );
    }

    public static function supoprtQueries($file_info_id)
    {
        // DB::update("
        //     UPDATE CANDIDATE_INFO C
        //     SET C.CUSTOMER_ID_CMS = (SELECT CLIENT_ID FROM FILE_INFO WHERE ID = :file_id),
        //         C.CARD_NO_MASKED = (SELECT EXISTING_CARD_NO_MASK FROM FILE_INFO WHERE ID = :file_id)
        //     WHERE C.FILE_INFO_ID = :file_id
        //     AND C.CANDIDATE_TYPE = :candidate_type
        //     AND C.CUSTOMER_ID_CMS IS NULL
        // ", [
        //     'file_id' => $file_info_id,
        //     'candidate_type' => 'Applicant',
        // ]);
        

        // DB::update("
        //     UPDATE CANDIDATE_INFO C
        //     SET C.CUSTOMER_ID_CMS = NULL,
        //         C.CARD_NO_MASKED = NULL
        //     WHERE C.FILE_INFO_ID = :file_id
        // ", [
        //     'file_id' => $file_info_id
        // ]);
    }
}