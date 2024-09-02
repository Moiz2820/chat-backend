<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    //
    public function signup(Request $request)
    {

        $valid = $request->validate([
            'first_name' => 'required|regex:/(^[a-z A-Z]+[a-z A-Z\\-]*$)/u',
            'last_name' => 'nullable|regex:/(^[a-z A-Z]+[a-z A-Z\\-]*$)/u',
            'email' => 'email|nullable',
            'token' => 'required'
        ]);
        // return $request->all();
        $auth = Firebase::auth();
        try {
            $uid = $request->token;
            $verifiedIdToken = $auth->verifyIdToken($uid);
            $uid = $verifiedIdToken->claims()->get('sub');
            $user = $auth->getUser($uid);

            $data = User::where('email', '=', $user->email)->first();
            if ($data) {
                $token = Str::random(60);
                $data->api_token = $token;
                $data->save();
                return response()->json([
                    "user" => $data,
                    "token" => $token
                ]);
            } else {
                $tokenn = Str::random(60);

                $users = new User([
                    'first_name' => $request->first_name ?? explode('@', $user->email)[0],
                    'email' => $user->email,
                    'api_token' => $tokenn,
                    'type' => "user",
                    'status'=>"Active"
                ]);

                if (isset($request->password))
                    $users['password'] = Hash::make($request->password);

                if (isset($request->last_name))
                    $users->last_name = $request->last_name;
                $users->save();
                $user = User::find($users->id);
                return response()->json([
                    "user" => $user,
                    "token" => $tokenn
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json(["message" => $th->getMessage()], 400);
        }

    }

    public function login(Request $request)
    {

        $valid = $request->validate([
            'token' => 'required'
        ]);
        $auth = Firebase::auth();
        try {
            $uid = $request->token;
            $verifiedIdToken = $auth->verifyIdToken($uid);
            $uid = $verifiedIdToken->claims()->get('sub');
            $user = $auth->getUser($uid);
            $data = User::where('email', '=', $user->email)->first();
            if ($data) {
                $token = Str::random(60);
                $data->api_token = $token;
                $data->save();
                return response()->json([
                    "user" => $data,
                    "token" => $token
                ]);
            } else {
                return response()->json(["message" => "you have to register first"], 422);
            }
        } catch (\Throwable $th) {
            return response()->json(["message" => $th->getMessage()], 400);
        }

    }

    public function logout()
    {
        try {
            User::whereId(auth()->user()->id)->update(['api_token' => null, "fcm_token" => null]);
            return response()->json(["message" => "User logout successfully"]);
        } catch (\Throwable $th) {
            return response()->json(["message" => $th->getMessage()], 422);
        }
    }
}
