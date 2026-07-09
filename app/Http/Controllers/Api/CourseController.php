<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // GET /api/cfp/courses — public, liste toutes les formations
    public function index()
    {
        $courses = Formation::with('formateur:id,name')
            ->withCount('inscriptions')
            ->get();

        return response()->json($courses);
    }

    // GET /api/cfp/courses/{course} — public, détail
    public function show(Formation $course)
    {
        $course->load('formateur:id,name')
            ->loadCount('inscriptions');

        return response()->json($course);
    }

    // GET /api/cfp/my-courses — formateur uniquement
    public function myCourses(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('formateur')) {
            return response()->json([
                'message' => 'Seul un formateur peut consulter ses formations.',
            ], 403);
        }

        $courses = $user->formationsEnseignees()
            ->withCount('inscriptions')
            ->get();

        return response()->json($courses);
    }

    // GET /api/cfp/my-register-courses — apprenant uniquement
    public function myRegisterCourses(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('apprenant')) {
            return response()->json([
                'message' => 'Seul un apprenant peut consulter ses inscriptions.',
            ], 403);
        }

        $courses = $user->formations()
            ->with('formateur:id,name')
            ->get();

        return response()->json($courses);
    }

    // POST /api/cfp/courses — formateur uniquement
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('formateur')) {
            return response()->json([
                'message' => 'Seul un formateur peut créer une formation.',
            ], 403);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course = Formation::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'formateur_id' => $user->id,
        ]);

        return response()->json($course, 201);
    }

    // PUT /api/cfp/courses/{course} — propriétaire uniquement
    public function update(Request $request, Formation $course)
    {
        $user = $request->user();

        if ($course->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez modifier que vos propres formations.',
            ], 403);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    // DELETE /api/cfp/courses/{course} — propriétaire uniquement
    public function destroy(Request $request, Formation $course)
    {
        $user = $request->user();

        if ($course->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez supprimer que vos propres formations.',
            ], 403);
        }

        $course->delete();

        return response()->json(['message' => 'Formation supprimée.']);
    }
}
