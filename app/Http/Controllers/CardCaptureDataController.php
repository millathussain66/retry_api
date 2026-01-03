<?php

namespace App\Http\Controllers;

use App\Models\CardCaptureData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardCaptureDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $data = DB::table('file_info')
                ->leftJoin('candidate_info', function ($join) {
                    $join->on('file_info.id', '=', 'candidate_info.file_info_id')
                        ->where('candidate_info.candidate_type', 'Applicant');
                })
                ->select(
                    'file_info.id',
                    'file_info.file_sl_no',
                    'file_info.lead_id',
                    'candidate_info.candidate_name'
                )
                ->paginate(10);

            return response()->json($data);
        }

        return view('card_capture.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CardCaptureData $cardCaptureData)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CardCaptureData $cardCaptureData)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CardCaptureData $cardCaptureData)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CardCaptureData $cardCaptureData)
    {
        //
    }
}
