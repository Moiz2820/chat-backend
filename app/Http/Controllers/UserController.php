<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //

    public function users(Request $request){
        $users = User::where('id' ,'!=', $request->user()->id)->get();
        return response()->json($users,200);
    }

    public function user(User $user){
        return response()->json($user,200);

    }
}
