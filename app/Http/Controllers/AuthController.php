<?php
namespace App\Http\Controllers;
use App\Http\Resources\UserResource;
use App\Models\ConfirmCode;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function authByPhone(Request $request)
    {
        $data = $request->validate([
            'identity' => 'required|string|max:191',
            'code' => 'required|array',
        ]);
        $code = implode('', $data['code']);
        $identity = $data['identity'];
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $type = 'email';
        } else {
            $type = 'phone';
            $digits = preg_replace('/\D+/', '', $identity);
            if (preg_match('/^\d{10,15}$/', $digits)) {
                $identity = $digits;
            } else {
                return response()->json(['message' => 'Неверный код', 'code' => $code, 'identity' => $identity], 422);
            }
        }

        $confirmCode = ConfirmCode::where($type, $identity)
            ->where('send_type', $type)
            ->where('code', $code)
            ->where('type', 'auth')
            ->where('used', false)
            ->latest()
            ->first();

        if (!$confirmCode) {
            return response()->json(['message' => 'Неверный код'], 422);
        }
        $confirmCode->update(['used' => true]);
        if ($confirmCode->expires_at->timestamp <= Carbon::now()->timestamp) {
            return response()->json(['message' => 'Срок действия кода истек'], 425);
        }

        return DB::transaction(function () use ($identity, $type) {
            $isNew = false;
            $user = User::where($type, $identity)->first();
            if (!$user) {
                $isNew = true;
                $userData = [
                    'name' => null,
                    $type => $identity,
                    'password' => Hash::make(Str::random(32)),
                    'is_subscribed' => false,
                    'is_accepted' => false,
                    'pass_generated' => true,
                    'balance' => 0,
                ];
                if ($type === 'email') {
                    $userData['email_verified_at'] = now();
                }
                $user = User::create($userData);
                event(new Registered($user));
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => new UserResource($user),
                'token' => $token,
                'is_new' => $isNew
            ]);
        });
    }



    public function logout(Request $r) {
        $r->user()->currentAccessToken()?->delete();
        return ['ok'=>true];
    }
}
