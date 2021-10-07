<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\BaseObject;
use Amuz\XePlugin\ApplicationHelper\Models\AhUserToken;
use App\Http\Controllers\Auth\AuthController;
use Faker\Provider\Base;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use XeFrontend;
use XePresenter;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Config\Repositories\CacheDecorator;
use Xpressengine\Config\Repositories\DatabaseRepository;
use Xpressengine\Keygen\Keygen;
use Xpressengine\User\EmailBroker;
use Xpressengine\User\Guard;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserHandler;

class Controller extends BaseController
{
    use AuthenticatesUsers;

    /**
     * key generator instance
     *
     * @var Keygen
     */
    protected $keygen;

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * @var UserHandler
     */
    protected $handler;

    /**
     * @var EmailBroker
     */
    protected $emailBroker;

    public function __construct(Keygen $keygen)
    {
        $this->auth = app('auth');
        $this->handler = app('xe.user');
        $this->emailBroker = app('xe.auth.email');
        $this->keygen = $keygen;
    }

    public function index()
    {
        $title = '애플리케이션 API 헬퍼';

        // set browser title
        XeFrontend::title($title);
        return \XePresenter::make('ApplicationHelper::views.index', []);
    }

    public function getLang($locale = 'ko'){
        $lang_list = \DB::table('translation')->where('locale',$locale)->get();
        $retObj = new BaseObject();
        $retObj->set('translation',$lang_list);
        return $retObj->output();
    }

    public function getConfig(){
        $site_key = \XeSite::getCurrentSiteKey();
        $config_list = \DB::table('config')->select('name','vars')->where('site_key',$site_key)->get();
        $retObj = new BaseObject();
        $retObj->set('site_key',$site_key);
        $retObj->set('config_list',$config_list);
        return $retObj->output();
    }

    public function getNavigator($menu_key){
        $site_key = \XeSite::getCurrentSiteKey();
        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        $deliver_menus = $ah_config->get('navigator');
        $menu_id = array_get($deliver_menus,$menu_key);
        if($menu_id == null) return;

        $instance_configs = $ah_config->get('instances');

        $menu_list = \DB::table('menu_item')->where('menu_id',$menu_id)->where('site_key',$site_key)->get();
        foreach($menu_list as $menu){
            $skin = array_get(array_get($instance_configs,$menu->id,[]),'skin');
            $state = array_get(array_get($instance_configs,$menu->id,[]),'state');
            $menu->skin = $skin;
            $menu->state = $state;
        }

        $retObj = new BaseObject();
        $retObj->set('site_key',$site_key);
        $retObj->set('menu_list',$menu_list);
        return $retObj->output();
    }

    //for dynamic factory
    public function getTaxonomies($taxonomy_key) {
        $taxonomies = app('overcode.df.taxonomyHandler')->getTaxonomies($taxonomy_key);
        if($taxonomies == null) return;

        foreach($taxonomies as $taxonomy) {
            $taxonomy->name = xe_trans($taxonomy->name);
            $items = app('overcode.df.taxonomyHandler')->getCategoryItemsTree($taxonomy->id)->toArray();
            sort($items);

            $taxonomy->item = $items;
        }

        return XePresenter::makeApi(['error' => 0, 'message' => 'Complete', 'data' => $taxonomies]);
    }

    public function getPermission(){
        $site_key = \XeSite::getCurrentSiteKey();
        $permission_list = \DB::table('permissions')->select('name','grants')->where('site_key',$site_key)->get();
        $retObj = new BaseObject();
        $retObj->set('site_key',$site_key);
        $retObj->set('permission_list',$permission_list);
        return $retObj->output();
    }

    public function get_videos_vimeo(){
//        ->select('id','directory_id','name','video_duration','thumbnail','thumbnail_overlay')
        $vimeo_list = \DB::table('vimeo_video')->where('delete_status','N')->get();
        $retObj = new BaseObject();
        $retObj->set('vimeo_list',$vimeo_list);
        return $retObj->output();
    }

    public function get_keychain(){
        try{
            $keychain = app('amuz.keychain');
            $retObj = new BaseObject();
            $retObj->set('key_list',$keychain->getUniqueKeys());
            return $retObj->output();
        }catch (\Exception $e){
            throw new \Exception("키체인 플러그인이 설치되지 않았습니다.");
        }
    }

    public function getInstance(){
        $site_key = \XeSite::getCurrentSiteKey();
        $config_list = \DB::table('instance_route')->select('url','module','instance_id')->where('site_key',$site_key)->get();
        $retObj = new BaseObject();
        $retObj->set('site_key',$site_key);
        $retObj->set('instance_list',$config_list);
        return $retObj->output();
    }

    public function closer(Request $request){
        $retObj = new BaseObject();
        $retObj->set('request',$request->all());
        return $retObj->output();
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tokenLogin(Request $request)
    {
        $retObj = $this->checkDeviceConnect($request);
        if($request->hasHeader('X-AMUZ-REMEMBER-TOKEN') && $request->hasHeader('X-AMUZ-DEVICE-UUID')){
            $token_info = AhUserToken::where('device_id',$request->header('X-AMUZ-DEVICE-UUID'))->where('token',$request->header('X-AMUZ-REMEMBER-TOKEN'))->first();
            if($token_info == null){
                $retObj->addError('ERR_BROKEN_SESSION','잘못된 토큰이 전달되었습니다.');
            }else{
                $user = User::find($token_info->user_id);
                $this->auth->login($user);

                $retObj->setMessage("로그인에 성공하였습니다.");
                $retObj->set('user',$user);
                $retObj->set('remember_token',$token_info->token);
            }
        }else{
            $retObj->addError('ERR_BROKEN_SESSION','잘못된 토큰이 전달되었습니다.');
        }

        return $retObj->output();
    }
    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postLogin(Request $request)
    {
        $retObj = $this->checkDeviceConnect($request);

        $this->checkCaptcha();

        $credentials = $request->only('email', 'password');

        $credentials['email'] = trim($credentials['email']);

        $credentials['status'] = [User::STATUS_ACTIVATED, User::STATUS_PENDING_ADMIN, User::STATUS_PENDING_EMAIL];


        if ($this->auth->attempt($credentials, true)) {
            $user = $this->auth->user();

            switch ($user->status) {
                case User::STATUS_PENDING_ADMIN:
                    $retObj->addError('ERR_PENDING_ADMIN','관리자 승인 대기중입니다.');
                    break;

                case User::STATUS_PENDING_EMAIL:
                    $retObj->addError('ERR_PENDING_EMAIL','메일 인증이 완료되지 않았습니다.');
                    break;

                default:
                    $token = $this->keygen->generate();
                    $deviceInfo = $retObj->get('deviceInfo');
                    $deviceInfo['token'] = $token;
                    $deviceInfo['user_id'] = $user->id;

                    $user_token = AhUserToken::firstOrNew(['device_id' => $deviceInfo['device_id']]);
                    foreach($deviceInfo as $key => $val) $user_token->{$key} = $val;

                    $user_token->save();

                    $retObj->setMessage("로그인에 성공하였습니다.");
                    $retObj->set('user',$user);
                    $retObj->set('remember_token',$token);
                    break;
            }
        }else{
            $retObj->addError('ERR_AccountNotFoundOrDisabled',xe_trans('xe::msgAccountNotFoundOrDisabled'));
        }
        return $retObj->output();
    }

    protected function checkDeviceConnect(Request $request){
        $retObj = new BaseObject();
        $deviceInfo = [
            'device_name' => $request->header('X-AMUZ-DEVICE-NAME'),
            'device_version' => $request->header('X-AMUZ-DEVICE-VERSION'),
            'device_id' => $request->header('X-AMUZ-DEVICE-UUID'),
        ];
        foreach($deviceInfo as $key => $val){
            if($val == null){
                $retObj->addError('ERR_NONE_ALLOW',sprintf('허용되지 않은 접근입니다. %s가 필요합니다.',$key));
                return $retObj->output();
            }
        }
        $retObj->set('deviceInfo',$deviceInfo);
        return $retObj;
    }


    /**
     * Check captcha
     *
     * @return void
     */
    protected function checkCaptcha()
    {
        $config = app('xe.config')->get('user.register');
        if ($config->get('useCaptcha', false) === true) {
            if (app('xe.captcha')->verify() !== true) {
                throw new HttpException(Response::HTTP_FORBIDDEN, xe_trans('xe::msgFailToPassCAPTCHA'));
            }
        }
    }
}
