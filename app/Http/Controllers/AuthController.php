<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\PasswordReset;
use Illuminate\Support\Facades\Validator;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Notifications\SignupActivate;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] user_type
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $validator = Validator::make(
            [
                'name' => $request->name,
                'password' => $request->password,
                'email' => $request->email,
                'user_type' => $request->user_type
            ],
            [
                'name' => 'required',
                'password' => 'required|min:8',
                'email' => 'required|email|unique:users',
                'user_type' => 'required|integer'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'user_type' => $request->user_type,
            'password' => bcrypt($request->password),
            'activation_token' => str_random(60)
        ]);
        $user->save();
        $user->notify(new SignupActivate($user));

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $validator = Validator::make(
            [
                'password' => $request->password,
                'email' => $request->email,
                'user_type' => $request->user_type
            ],
            [
                'password' => 'required',
                'email' => 'required|email',
                'remember_me' => 'boolean'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        $credentials = request(['email', 'password']);
        $credentials['active'] = 1;
        $credentials['deleted_at'] = null;

        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function signupActivate($token)
    {
    $user = User::where('activation_token', $token)->first();
    if (!$user) {
        return response()->json([
            'message' => 'This activation token is invalid.'
        ], 404);
    }
    $user->active = true;
    $user->activation_token = '';
    $user->save();
    return $user;
    }

   /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
     public function create(Request $request)
     {
        $validator = Validator::make(
            [
                'email' => $request->email
            ],
            [
                'email' => 'required|string|email'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

         $user = User::where('email', $request->email)->first();
         if (!$user)
             return response()->json([
                 'message' => 'We can\'t find a user with that e-mail address.'
             ], 404);
         $passwordReset = PasswordReset::updateOrCreate(
             ['email' => $user->email],
             [
                 'email' => $user->email,
                 'token' => str_random(60)
              ]
         );
         if ($user && $passwordReset)
             $user->notify(
                 new PasswordResetRequest($passwordReset->token)
             );
         return response()->json([
             'message' => 'We have e-mailed your password reset link!'
         ]);
     }
     /**
      * Find token password reset
      *
      * @param  [string] $token
      * @return [string] message
      * @return [json] passwordReset object
      */
     public function find($token)
     {
         $passwordReset = PasswordReset::where('token', $token)
             ->first();
         if (!$passwordReset)
             return response()->json([
                 'message' => 'This password reset token is invalid.'
             ], 404);
         if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
             $passwordReset->delete();
             return response()->json([
                 'message' => 'This password reset token is invalid.'
             ], 404);
         }
         return response()->json($passwordReset);
     }
      /**
      * Reset password
      *
      * @param  [string] email
      * @param  [string] password
      * @param  [string] password_confirmation
      * @param  [string] token
      * @return [string] message
      * @return [json] user object
      */
     public function reset(Request $request)
     {

        $validator = Validator::make(
            [
                'email' => $request->email,
                'password' => $request->password,
                'token' => $request->token,
            ],
            [
                'email' => 'required|string|email',
                'password' => 'required|string',
                'token' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

         $passwordReset = PasswordReset::where([
             ['token', $request->token],
             ['email', $request->email]
         ])->first();
         if (!$passwordReset)
             return response()->json([
                 'message' => 'This password reset token is invalid.'
             ], 404);
         $user = User::where('email', $passwordReset->email)->first();
         if (!$user)
             return response()->json([
                 'message' => 'We can\'t find a user with that e-mail address.'
             ], 404);
         $user->password = bcrypt($request->password);
         $user->save();
         $passwordReset->delete();
         $user->notify(new PasswordResetSuccess($passwordReset));
         return response()->json($user);
     }
}
