<?php

namespace App\Jobs;

use App\Services\CreditCardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditCardJob implements ShouldQueue
{
    use Queueable;

    public $request;
    /**
     * Create a new job instance.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $result = CreditCardService::prepareCardAPIData($this->request->file_info_id);

        if (empty($result['applicant']->customer_id_cms)) {
            $proceedOne = CreditCardService::beforeCallApi(1, $this->request->lead_id, $this->request->file_info_id, $this->request->candidate_info_id);
            $responseDataOne = null;
            if ($proceedOne) {
                $responseDataOne = json_decode($proceedOne->response, true);
            } else {
                $data = [
                    'lead_id'           => $result['lead_id'] ?? $this->request->lead_id,
                    'file_info_id'      => $result['file_info_id'] ?? $this->request->file_info_id,
                    'candidate_info_id' => $result['candidate_info_id'] ?? $this->request->candidate_info_id,
                    'file_sl_no'        => $result['file_sl_no'] ?? null,
                    'etin'              => $result['etin'] ?? null,
                ];
                $responseDataOne = CreditCardService::cleintIdByTin($data);
            }

            $item = json_decode($responseDataOne['resp'], true);
            $clientId = $item['responseData'][0]['clientId'] ?? '';

            if(!empty($clientId)){
                  // Update cliendId and process Existing-to-Bank
                // --------------------------------------------------
                // 1. Update FILE_INFO
                DB::table('FILE_INFO')
                    ->where('ID', $this->request->file_info_id)
                    ->update(['CLIENT_ID' => $clientId]);

                // 2. Update CANDIDATE_INFO
                DB::table('CANDIDATE_INFO')
                    ->where('FILE_INFO_ID', $this->request->file_info_id)
                    ->where('CANDIDATE_TYPE', 'Applicant')
                    ->update([
                        'CUSTOMER_ID_CMS' => $clientId
                    ]);
                
                // 3. Update customer_id_cms of Applicant data
                $result['applicant']->customer_id_cms = $clientId;

                $proceedTwo = CreditCardService::beforeCallApi(2, $this->request->lead_id, $this->request->file_info_id, $this->request->candidate_info_id);
                $responseDataTwo = null;
                if ($proceedTwo) {
                    $responseDataTwo = json_decode($proceedTwo->response, true);
                } else {
                    // 4. Call E2B process
                    $responseDataTwo = CreditCardService::_processE2B($this->request->file_info_id, $result);
                }
            }
        }

        // // First API Call Start
        // // ===================================================================
        // $proceedOne = CreditCardService::beforeCallApi(1, $this->request->lead_id, $this->request->file_info_id, $this->request->candidate_info_id);
        // $responseDataOne = null;
        // if ($proceedOne) {
        //     $responseDataOne = json_decode($proceedOne->response, true);
        // } else {
        //     $responseDataOne = CreditCardService::apiCallSimulation1($this->request);
        // }
        // // ===================================================================
        // // First API Call End



        // // Second API Call
        // // ===================================================================
        // $proceedTwo = CreditCardService::beforeCallApi(2, $this->request->lead_id, $this->request->file_info_id, $this->request->candidate_info_id);
        // $responseDataTwo = null;
        // if ($proceedTwo) {
        //     $responseDataTwo = json_decode($proceedTwo->response, true);
        // } else {
        //     $responseDataTwo = CreditCardService::apiCallSimulation2($this->request);
        // }
    }
}
