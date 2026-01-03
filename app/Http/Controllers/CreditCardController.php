<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\CreditCardJob;
use App\Services\CreditCardService;
use Illuminate\Support\Facades\DB;

class CreditCardController extends Controller
{
    public function createCreditCard(Request $request)
    {
        $result = CreditCardService::prepareCardAPIData($request->file_info_id);

        if (empty($result['applicant']->customer_id_cms)) {
            $proceedOne = CreditCardService::beforeCallApi(1, $request->lead_id, $request->file_info_id, $request->candidate_info_id);
            $responseDataOne = null;
            if ($proceedOne) {
                $responseDataOne = json_decode($proceedOne->response, true);
            } else {
                $data = [
                    'lead_id'           => $result['applicant']->lead_id,
                    'file_info_id'      => $result['applicant']->file_info_id,
                    'candidate_info_id' => $result['applicant']->candidate_info_id,
                    'file_sl_no'        => $result['applicant']->file_sl_no ?? null,
                    'etin'              => $result['etin'] ?? null,
                ];
                $responseDataOne = CreditCardService::cleintIdByTin($data);
            }

            $clientId = $responseDataOne['responseData'][0]['clientId'] ?? '';

            if (!empty($clientId)) {
                // Update cliendId and process Existing-to-Bank
                // --------------------------------------------------
                // 1. Update FILE_INFO
                // DB::table('FILE_INFO')
                //     ->where('ID', $request->file_info_id)
                //     ->update(['CLIENT_ID' => $clientId]);

                // 2. Update CANDIDATE_INFO
                // DB::table('CANDIDATE_INFO')
                //     ->where('FILE_INFO_ID', $request->file_info_id)
                //     ->where('CANDIDATE_TYPE', 'Applicant')
                //     ->update([
                //         'CUSTOMER_ID_CMS' => $clientId
                //     ]);

                // 3. Update customer_id_cms of Applicant data
                $result['applicant']->customer_id_cms = $clientId;

                $proceedTwo = CreditCardService::beforeCallApi(2, $request->lead_id, $request->file_info_id, $request->candidate_info_id);
                $responseDataTwo = null;
                if ($proceedTwo) {
                    $responseDataTwo = json_decode($proceedTwo->response, true);
                } else {
                    // 4. Call E2B process
                    $responseDataTwo = CreditCardService::_processE2B($request->file_info_id, $result);
                }
            }
            $responseDataTwo = CreditCardService::_processN2B($request->file_info_id, $result);
        }else{
            // Directly process N2B
            $responseDataTwo = CreditCardService::_processN2B($request->file_info_id, $result);
        }


        // dispatch(new CreditCardJob((object)$request->all()));
        // return response()->json(['message' => 'Credit card created successfully']);
    }
}
