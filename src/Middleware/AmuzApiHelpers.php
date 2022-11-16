<?php
namespace Amuz\XePlugin\ApplicationHelper\Middleware;

use Amuz\XePlugin\ApplicationHelper\Models\AhUserToken;
use Amuz\XePlugin\ApplicationHelper\BaseObject;
use Closure;
use Faker\Provider\Base;
use Xpressengine\Http\Request;
use Xpressengine\User\Models\User;


class AmuzApiHelpers
{
    public function handle(Request $request, Closure $next)
    {
        //리멤버토큰이 들어오면 계속 로그인을 유지시켜준다.
        if ($request->hasHeader('X-AMUZ-REMEMBER-TOKEN') && $request->hasHeader('X-AMUZ-DEVICE-UUID')) {
//        if ($request->wantsJson() && $request->hasHeader('X-AMUZ-REMEMBER-TOKEN') && $request->hasHeader('X-AMUZ-DEVICE-UUID')) {
            $auth = app('auth');

            $token = AhUserToken::where('token',$request->header('X-AMUZ-REMEMBER-TOKEN'))->where('device_id',$request->header('X-AMUZ-DEVICE-UUID'))->first();

            if($token != null){
                $user = User::find($token->user_id);
                if(!$user){
                    $token->delete();
//                    $retObj = new BaseObject();
//                    $retObj->addError('ERR_BROKEN_SESSION','세션이 만료되었거나 로그아웃 되었습니다.');
//                    return $retObj->output();
                }else{
                    $auth->login($user);

                    if(\Schema::hasTable('sendbird_user_token')) {
                        $this->updateSendbirdToken($token);
                    }
                }
//            }else{
//                $retObj = new BaseObject();
//                $retObj->addError('ERR_BROKEN_SESSION','세션이 만료되었거나 로그아웃 되었습니다.');
//                return $retObj->output();
            }
        }
        return $next($request);
    }

    private function updateSendbirdToken($tokenInfo){
        //토큰이 있으면
        if($tokenInfo->push_token == '' || $tokenInfo->push_token == null) return;

        // 필요한 플러그인이 활성화 되어있는지 검사한다.
        $pluginHandler = app('xe.plugin');
        $sendbirdChat = $pluginHandler->getPlugin('sendbird_chat');
        if (!$sendbirdChat || $sendbirdChat->getStatus() != 'activated') return;

        $tokenType = "gcm";
        if(str_starts_with($tokenInfo->device_name,"iPhone") || str_starts_with($tokenInfo->device_name,"iPad")) $tokenType = "apns";
        app('amuz.sendbird.chat')->updateUserPushToken($tokenInfo->user_id,$tokenType,$tokenInfo->push_token);
        if(\Schema::hasTable('sendbird_user_token')) app('amuz.sendbird.chat')->syncUserData($tokenInfo->user_id);
    }
}
