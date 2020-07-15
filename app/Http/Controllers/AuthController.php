<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Hashing\HashManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */
    protected $hasher;
    protected $userService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserService $userService, HashManager $hasher)
    {
        $this->userService = $userService;
        $this->hasher = $hasher;
    }

    /**
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthenticationException
     * @throws \SMartins\PassportMultiauth\Exceptions\MissingConfigException
     */
    public function login(LoginRequest $request)
    {
        $credentials = array_values($request->only('email', 'password'));
        if (!$user = $this->attempt(...$credentials)) {
            throw new NotFoundHttpException("User name or password is wrong");
        }
        $token = $user->createToken('Access Token')->accessToken;

        return $this->successResponse(compact('user', 'token'));
    }

    /**
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function attempt($email, $password)
    {
        if (!$user = User::where('email', $email)->first()) {
            return null;
        }
        if (!$this->hasher->check($password, $user->getAuthPassword())) {
            return null;
        }

        return $user;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return $this->successResponse(['data' => Auth::user()]);
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->token()->revoke();
        } else {
            throw new UnauthorizedHttpException("Bear");
        }
        return $this->errorResponse(['code' => 204]);
    }
}
