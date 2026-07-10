<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // GET /api/cfp/courses — public, list all courses
    public function index()
    {
        $courses = Course::with('instructor:id,name')
            ->withCount('registrations')
            ->get();

        return response()->json($courses);
    }

    // GET /api/cfp/courses/{course} — public, detail
    public function show(Course $course)
    {
        $course->load('instructor:id,name')
            ->loadCount('registrations');

        return response()->json($course);
    }

    // GET /api/cfp/my-courses — instructor only
    public function myCourses(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('instructor')) {
            return response()->json([
                'message' => 'Only an instructor can view their courses.',
            ], 403);
        }

        $courses = $user->taughtCourses()
            ->withCount('registrations')
            ->get();

        return response()->json($courses);
    }

    // GET /api/cfp/my-enrolled-courses — student only
    public function myEnrolledCourses(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('student')) {
            return response()->json([
                'message' => 'Only a student can view enrolled courses.',
            ], 403);
        }

        $courses = $user->courses()
            ->with('instructor:id,name')
            ->get();

        return response()->json($courses);
    }

    // POST /api/cfp/courses — instructor only
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('instructor')) {
            return response()->json([
                'message' => 'Only an instructor can create a course.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course = Course::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructor_id' => $user->id,
        ]);

        return response()->json($course, 201);
    }

    // PUT /api/cfp/courses/{course} — owner only
    public function update(Request $request, Course $course)
    {
        $user = $request->user();

        if ($course->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'You can only update your own courses.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    // DELETE /api/cfp/courses/{course} — owner only
    public function destroy(Request $request, Course $course)
    {
        $user = $request->user();

        if ($course->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'You can only delete your own courses.',
            ], 403);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted.']);
    }
}
