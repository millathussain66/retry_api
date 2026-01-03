<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommonService
{

    public static function convertToUpperCase($request)
    {
        if (is_array($request)) {
            foreach ($request as $key => $value) {
                if (is_string($value)) {
                    $request[$key] = strtoupper($value);
                } elseif (is_object($value)) {
                    foreach ($value as $subKey => $subValue) {
                        if (is_string($subValue)) {
                            $value->$subKey = strtoupper($subValue);
                        }
                    }
                }
            }
        } else {
            $request = strtoupper($request);
        }

        return $request;
    }


    public static function pushAPILogInQueue($data)
    {

        DB::table('retry_api_queue')->insert([
            'api_id'            => $data['api_id'],
            'api_name'          => self::getApiNameById($data['api_id']),
            'lead_id'           => $data['lead_id'],
            'file_info_id'      => $data['file_info_id'],
            'candidate_info_id' => $data['candidate_info_id'],
            'file_sl_no'        => $data['file_sl_no'],
            'payload'           => json_encode($data['requestPayload']),
            'response'          => json_encode($data['response']),
            'success_status'    => $data['response']['responseCode'] == '100' ? 1 : 0,
            'last_attempted_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if ($data['response']['responseCode'] != '100') {
            Log::info('API Response Error: ' . json_encode($data['response']));
            throw new Exception("API failed: Missing customer/card information");
        }
    }

    public static function getApiNameById($apiId)
    {
        $api = DB::table('card_api_list')->where('id', $apiId)->first();
        return $api ? $api->api_name : null;
    }
}
