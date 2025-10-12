<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): UserResource
    {

        return new UserResource($request->user());
    }

    public function accept(Request $request): UserResource
    {
        $data = $request->validate([
            'subscribe' => 'required|boolean',
            'user_name' => 'required|string',
        ]);
        $request->user()->update([
            'is_subscribed' => $data['subscribe'],
            'is_accepted' => true,
            'name' => $data['user_name']
        ]);

        return new UserResource($request->user());
    }
}
