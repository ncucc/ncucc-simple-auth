<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ncucc\SimpleAuth\Client;
use Config;
use App\User;

class SigninController extends Controller
{
    private $client;
    
    function __construct() {
        $this->client = new Client(Config::get('simple-auth'));
    }
    
    public function signin(Request $request) {
        try {
            $token = $this->client->retrieveToken($request->input('code'));
            $info = $this->client->retrieveUserInfo($token);
            if (property_exists($info, 'identifier')) {
                
                $user = User::firstOrCreate(['name' => $info->identifier], [
                    'email' => property_exists($info, 'email') ? $info->email : '',
                    'password' => base64_encode(random_bytes(24))
                ]);

                Auth::login($user);
                $request->session()->put('userInfo', $info);
                
                return redirect()->intended('home');
            } else {
                dd($info);
            }
        } catch (\Exception $e) {
            // TODO: 失敗，請做適當的修改
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

    }
}
