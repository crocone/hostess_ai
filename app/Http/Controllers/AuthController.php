<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $r) {
        $data = $r->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:8'
        ]);
        $user = User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>bcrypt($data['password'])
        ]);
        return response()->json(['user'=>$user], 201);
    }

    public function login(Request $r) {
        $creds = $r->validate(['email'=>'required|email','password'=>'required']);
        if (!Auth::attempt($creds)) return response()->json(['message'=>'Invalid credentials'], 401);
        $token = $r->user()->createToken('api')->plainTextToken;
        return ['token'=>$token,'user'=>$r->user()];
    }

    public function logout(Request $r) {
        $r->user()->currentAccessToken()?->delete();
        return ['ok'=>true];
    }
}
