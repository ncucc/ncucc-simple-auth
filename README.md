# NCU Portal 4G Simple Auth

中央大學的 portal 系統提供多種的單一簽入協定包括第一代的 Webcomm 協定，
第二代的 OpenId 協定，第三代的 OAuth2 協定。其中 OpenID 及 OAuth2
都是走開放協定，Webcomm 協定則為制式的協定。

第四代的 Portal 導入新的簡單協定，簡稱為「 Simple 」或 「 Simple Auth 」，
基本上是 OAuth2 的簡化版，整個認證程序由 Portal 出發，直接將 code 
送到應用系統的端口，應用系統則利用 code 去取 token，再由 token 去取 resource。

在傳統的 OAuth2 中，應用系統會送一個用 state 來做狀態使用，在 Simple Auth 
在認證程序是由使用者在 portal 上選擇應用系統而出發的，因此沒有 state 可供確認。
為了彌補這個缺陷， Simple Auth 的 code 開頭是以 GMT 時間的 "yyyyMMddHHmmss"
(西元年，月，日，時，分秒)，Client 端可以由 code 前端的 timestamp 來確定這個
code 是否仍為有效；另外 Simple Auth 在 state 部份則放置了一份對 code 字串做的
SHA256-WITH-ECDSA 數位簽章，應用系統可以藉由數位簽章的檢查來確定這個 code
是否是為 portal 發送出來的。由於數位簽章是 binary 型式，所以 state 的值是數位簽章為
Base64 url encoding (without padding) 的型式。

當應用系統拿到 code 並驗證是合法後，可以用此 code 向 portal 取 Access Token，
從這段開始，跟 OAuth 實作是一樣的： 利用 Client Id 及 Client Secret
當帳號密碼，用 code 跟 portal 交換 token。 portal 會確定這個 code
確實是發給該應用系統的才決定是否交予 Token。

應用系統拿到 Token 之後，用這個 Token 即可索取登入授權的資訊。為了簡化認證的流程，
採用 Simple Auth 協定不再需要經由使用者同意授權，而使用 OAuth2 協定的，
仍然保持使用者可以決定是否授權。

這份 Simple Auth 的 php 程式碼是一個簡化版的，它沒有去檢驗 code 的有效性，
但這並不會造成太大的安全問題： 應用系統可能拿到一個假的 code 然後去向 portal
交換 Token，但一個假的 code 實際上是換不到 token 的，得到的結果就是登入失敗。

portal 的 public key 可以透過網頁取得:

[https://portal.ncu.edu.tw/apis/static/PublicKey.pem](https://portal.ncu.edu.tw/apis/static/PublicKey.pem)

### 如何使用

這程式因為只能給中央大學的 portal 使用，因此不想再去到 https://packagist.org/
去註冊，但程式碼仍放在 github 上。

本系統在 php 7.3 / Laravel 6.2 測試過，使用方式大致如下:

#### Step 1
到 portal 註冊一個應用系統，並選 Simple 為登入的方式

#### Step 2
將 supports/simple-auth.php 放置到 config 目錄下，在 .env 檔案中放置如下的內容。
（請依實際應用修改）
```
SIMPLE_AUTH_URL=https://portal-preview.cc.ncu.edu.tw/system/<change it>
SIMPLE_AUTH_CLIENT_ID="<change it>"
SIMPLE_AUTH_CLIENT_SECRET=<change it>
SIMPLE_AUTH_TOKEN_URL=https://portal-preview.cc.ncu.edu.tw/oauth2/token
SIMPLE_AUTH_USERINFO_URL=https://portal-preview.cc.ncu.edu.tw/apis/oauth/v1/info
```

#### Step 3
做一個登入的 Controller，如
```php
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
```

#### Step 4
並把 route 指到這個 controller, 如:
```php
Route::get('/signin', 'SigninController@signin');
```
