<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use Socialite;
use App\Mail\WelcomeUser;
use Illuminate\Support\Facades\Mail;
use Socialite\Two\GithubProvider;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logoutUser']);
    }

    public function authorizeUser()
    {
        return Socialite::driver('github')->scopes(['admin:org'])->redirect();
    }

    public function loginUser()
    {
        $github = Socialite::driver('github')->user();
        $email = getEmailByToken($github->token);
        $user = User::where('email', '=', $email)->first();
        if ($user === null) {
            $user = User::create([
              'name'             => $github->getName(),
              'email'            => $github->$email,
              'github_username'  => $github->getNickname(),
              'token'            => $github->token,
              'api_token'        => str_random(60),
            ]);
            Mail::to($user->email)->send(new WelcomeUser());
            Auth::login($user);

            return redirect()->intended('dashboard')->withSuccess(trans('alerts.loggedin'));
        }
        $user->update([
            'name'             => $github->getName(),
            'email'            => $github->$email,
            'github_username'  => $github->getNickname(),
            'token'            => $github->token,
        ]);
        Auth::login($user);

        return redirect()->intended('dashboard')->withSuccess(trans('alerts.loggedin'));
    }

    public function logoutUser()
    {
        Auth::logout();

        return redirect('')->withSuccess('Sucessfully logged out!');
    }
}
