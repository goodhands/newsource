<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\UpdatePreferencesRequest;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = User::create($request->all());

        $token = $user->createToken('api_token');

        return ['token' => $token->plainTextToken];
    }

    /**
     * Update the authenticated user's preferences.
     */
    public function updatePreferences(UpdatePreferencesRequest $request)
    {
        $user = $request->user();

        $user->update([
            'preferences' => $request->input('preferences')
        ]);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $user->preferences
        ]);
    }

    /**
     * Get the authenticated user's preferences.
     */
    public function getPreferences(Request $request)
    {
        return response()->json([
            'preferences' => $request->user()->preferences ?? []
        ]);
    }
}
