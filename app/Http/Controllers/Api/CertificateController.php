<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    // GET /api/cfp/my-certificates — student only
    public function myCertificates(Request $request)
    {
        $user = $request->user();

        $certificates = Certificate::whereHas('registration', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->with(['registration.course:id,title'])
            ->get();

        return response()->json($certificates);
    }

    // GET /api/cfp/verify/{uuid} — public, without authentication
    public function verify(string $uuid)
    {
        $certificate = Certificate::where('uuid', $uuid)
            ->with(['registration.user:id,name', 'registration.course:id,title'])
            ->first();

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found or invalid.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'uuid' => $certificate->uuid,
                'issued_at' => $certificate->issued_at,
                'student' => $certificate->registration->user->name,
                'course' => $certificate->registration->course->title,
            ],
        ]);
    }
}
