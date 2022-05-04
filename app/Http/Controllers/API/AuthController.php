<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validator = Validator::make($request->all(),[
            'login_info' => 'required',
            'password' => 'required'
        ])->stopOnFirstFailure(true);
        
        // validate error
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
        // create new user
        $user = new User();
        $result = $this->checkLoginInfo($request, $user);
            if($result) {
                return response()->json(['error' => $result['message']], $result['code']);
            }
        
        $user->name = $request->name;
        $user->save();
        
        $token = $user->createToken('auth_token')->plainTextToken;

        
        return response()->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer'],200);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(),[
            'login_info' => 'required',
            'password' => 'required'
        ])->stopOnFirstFailure(true);
        
        // validate error
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $user = User::where('email', '=', $request->login_info)
                    ->orWhere('phone', '=', $request->login_info)
                    ->first();
        
        if(! $user)
            return response()->json(['msg' => 'User does not exist'],401);
        
        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect Password'], 401);
        }
       
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['message' => 'Hi ' . $user->name . ', welcome to home', 'access_token' => $token, 'token_type' => 'Bearer',]);
    }

    public function logout() {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'You have successfully logged out and the token was successfully deleted'
        ];
    }
     // check valid email or phone
     public function checkLoginInfo(Request $request, User $newUser) {
        $login_info = $request->login_info; 
        $data = [];
        // check phone or email
        if (is_numeric($login_info)) {//phone
            /*
                can verify here valid phone no via sms code
            */
            // check valid phone no
            $validator = Validator::make($request->all(),[
                'login_info' => 'regex:/(09)[0-9]{9}/'
            ])->stopOnFirstFailure(true);
            if ($validator->fails()) {
                $data = ['message' => 'Invalid Phone number', 'code' => 400];
                return $data;
            }
            
            
            $newUser->phone = $login_info;
        }
        else {// email
            /*
                can verify here valid email via sms code
            */
            // check valid email
            $validator = Validator::make($request->all(),[
                'login_info' => 'email'
            ])->stopOnFirstFailure(true);

            if ($validator->fails()) {
                $data = ['message' => 'Invalid Email', 'code' => 400];
                return $data;
            }
            
            $newUser->email = $login_info;
        }
        
        $newUser->user_id = $this->getNewUserId();
        $hash_password = Hash::make($request->password);
        $newUser->password = $hash_password;

        return $data;
    }
    // get new user's user_id
    public function getNewUserId() {
        $row = User::all()->count();
        return 'u' . $row + 1;
    }
}
