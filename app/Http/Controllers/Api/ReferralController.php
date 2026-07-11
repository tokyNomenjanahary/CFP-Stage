<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $referrals = Referral::where('referrer_id', $request->user()->id)
            ->with('referred:id,name')
            ->get();

        return response()->json($referrals);
    }
}
