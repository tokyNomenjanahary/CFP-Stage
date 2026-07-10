<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    // POST /api/cfp/courses/{course}/register
    public function store(Request $request, Course $course)
    {
        $user = $request->user();

        if (! $user->hasRole('student')) {
            return response()->json([
                'message' => 'Only a student can register for a course.',
            ], 403);
        }

        // Prevent an instructor from registering for their own course.
        if ($course->instructor_id === $user->id) {
            return response()->json([
                'message' => 'You cannot register for your own course.',
            ], 422);
        }

        // Prevent duplicate registrations.
        $alreadyRegistered = Registration::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'message' => 'You are already registered for this course.',
            ], 422);
        }

        $registration = Registration::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'in_progress',
            'registered_at' => now(),
        ]);

        return response()->json($registration, 201);
    }

    // GET /api/cfp/courses/{course}/registrations — instructor only
    public function courseRegistrations(Request $request, Course $course)
    {
        $user = $request->user();

        if ($course->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'You can only view registrations for your own courses.',
            ], 403);
        }

        $registrations = $course->registrations()
            ->with('user:id,name,phone')
            ->with('certificate')
            ->get();

        return response()->json($registrations);
    }

    // PUT /api/cfp/registrations/{registration}/status — instructor only
    public function updateStatus(Request $request, Registration $registration)
    {
        $user = $request->user();

        if ($registration->course->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'You can only modify registrations for your own courses.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:in_progress,completed',
        ]);

        $registration->update(['status' => $validated['status']]);

        if ($validated['status'] === 'completed' && ! $registration->certificate) {
            Certificate::create([
                'registration_id' => $registration->id,
                'issued_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Registration status updated.',
            'registration' => $registration->fresh('certificate'),
        ]);
    }
}
