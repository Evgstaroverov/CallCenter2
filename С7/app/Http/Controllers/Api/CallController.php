<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function index()
    {
        return Call::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        $call = Call::create($validated + ['status' => 'new']);

        return response()->json($call, 201);
    }

    public function show(Call $call)
    {
        return $call;
    }

    public function update(Request $request, Call $call)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:new,in_progress,closed',
            'description' => 'nullable|string',
        ]);

        $call->update($validated);
        return $call;
    }

    public function destroy(Call $call)
    {
        $call->delete();
        return response()->json(null, 204);
    }
}