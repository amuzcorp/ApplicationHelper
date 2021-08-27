<?php
namespace Amuz\XePlugin\ApplicationHelper\Middleware;

use Amuz\Plugin\ApplicationHelper\Models\AhUserToken;
use Amuz\XePlugin\ApplicationHelper\BaseObject;
use Closure;
use Xpressengine\Http\Request;
use Xpressengine\User\Models\User;


class AmuzApiHelpers
{
    public function handle(Request $request, Closure $next)
    {
        //리멤버토큰이 들어오면 계속 로그인을 유지시켜준다.
        if ($request->wantsJson() && $request->hasHeader('X_AMUZ_REMEMBER_TOKEN') && $request->hasHeader('X_AMUZ_DEVICE_UUID')) {
            $auth = app('auth');

            $token = AhUserToken::where('token',$request->header('X_AMUZ_REMEMBER_TOKEN'))->where('device_id',$request->header('X_AMUZ_DEVICE_UUID'))->first();
            if($token != null){
                $user = User::find($token->user_id);
                $auth->login($user);
            }else{
                $auth->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $retObj = new BaseObject();
                $retObj->addError('ERR_BROKEN_SESSION','세션이 만료되었거나 로그아웃 되었습니다.');
                return $retObj->output();
            }
        }
        return $next($request);
    }
}
