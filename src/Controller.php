<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\Models\AhUserToken;
use Amuz\XePlugin\ApplicationHelper\Models\AhUserAppleInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Symfony\Component\HttpKernel\Exception\HttpException;
use XeFrontend;
use XePresenter;
use Schema;
use XeDB;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Keygen\Keygen;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Plugins\Board\ConfigHandler;
use Xpressengine\Plugins\Board\Models\Board;
use Xpressengine\Plugins\Board\Services\BoardService;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsAccountException;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsEmailException;
use Xpressengine\Plugins\SocialLogin\Handler;
use Xpressengine\Plugins\SocialLogin\Providers\KakaoProvider;
use Xpressengine\Support\Exceptions\HttpXpressengineException;
use Xpressengine\User\EmailBroker;
use Xpressengine\User\Guard;
use Xpressengine\User\Models\User;
use Xpressengine\User\Models\UserGroup;
use Xpressengine\User\UserHandler;

use Xpressengine\User\Models\User as XeUser;

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

    protected $xeDynamicField;
    protected $userTypeHandler;

    public function __construct(Keygen $keygen)
    {
        $this->auth = app('auth');
        $this->handler = app('xe.user');
        $this->emailBroker = app('xe.auth.email');
        $this->keygen = $keygen;


        $this->xeDynamicField = app('xe.dynamicField');
        $this->userTypeHandler = app('amuz.usertype.handler');
    }

    public function index()
    {
        $title = '애플리케이션 API 헬퍼';

        // set browser title
        XeFrontend::title($title);
        return \XePresenter::make('ApplicationHelper::views.index', []);
    }

    public function getLang($locale = 'ko'){
        $lang_list = \DB::table('translation')->where('locale',$locale)->whereNotIn('namespace',['logic_builder'])->get();
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

    public function getNavigator(BoardService $boardService,ConfigHandler $boardConfigHandler, CptModuleConfigHandler $cptModuleConfigHandler, $menu_key){
        $site_key = \XeSite::getCurrentSiteKey();
        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        $deliver_menus = $ah_config->get('navigator');
        $menu_id = array_get($deliver_menus,$menu_key);
        if($menu_id == null) return;

        $instance_configs = $ah_config->get('instances');

        $menu_list = \DB::table('menu_item')->where('menu_id',$menu_id)->where('site_key',$site_key)->orderBy('ordering','asc')->get();
        foreach($menu_list as $menu){
            $skin = array_get(array_get($instance_configs,$menu->id,[]),'skin','list');
            $menu->skin = $skin;

            switch($menu->type){
                case "board@board" :
                    $config = $boardConfigHandler->get($menu->id);
                    $menu->categories = [
                        'category' => [
                                'group' => 'tax_category',
                                'items' => $boardService->getCategoryItemsTree($config)
                            ]
                    ];
                    $menu->create_url = route('ahib::board_create',['instance_id' => $menu->id],false);
                    $menu->edit_url = route('ahib::board_edit',['instance_id' => $menu->id],false);
                    $menu->delete_url = route('ahib::board_delete',['instance_id' => $menu->id],false);
                    break;
                case "cpt@cpt" :
                    $config = $cptModuleConfigHandler->get($menu->id);
                    $taxonomyHandler = app('overcode.df.taxonomyHandler');
                    $taxonomies = $taxonomyHandler->getTaxonomies($config->get('cpt_id'));
                    $categories = [];

                    foreach($taxonomies as $taxonomy) {
                        $categories[$taxonomy->extra->slug]['group'] = $taxonomyHandler->getTaxFieldGroup($taxonomy->id);
                        $categories[$taxonomy->extra->slug]['items'] = $taxonomyHandler->getCategoryItemsTree($taxonomy->id,$categories[$taxonomy->extra->slug]['group']);
                    }
                    $menu->categories = $categories;

                    $menu->create_url = route('ahib::cpt_create',['slug' => $menu->url],false);
                    $menu->edit_url = route('ahib::cpt_edit',['slug' => $menu->url],false);
                    $menu->delete_url = route('ahib::cpt_delete',['slug' => $menu->url],false);
//                    $menu->create_url = route('ahib::cpt_create',['cpt_id'=>$config->get('cpt_id')],false);
                case "widgetpage@widgetpage" :
                    break;
                default :
                    break;
            }
        }

        $retObj = new BaseObject();
        $retObj->set('site_key',$site_key);
        $retObj->set('menu_list',$menu_list);
//        dd($retObj);
        return $retObj->output();
    }

    //for dynamic factory
    public function getTaxonomiesByTaxonomyId($taxonomyKey) {
        $taxonomy = CategoryExtra::find($taxonomyKey);
        if($taxonomy == null) $taxonomy = CategoryExtra::where('slug',$taxonomyKey)->first();

        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $items = $taxonomyHandler->getCategoryItemsTree($taxonomy->category_id)->toArray();
        sort($items);

        return XePresenter::makeApi(['error' => 0, 'message' => 'Complete', 'data' => [
                'tree' => $items,
                'items' => $taxonomyHandler->getCategoryItemAttributes($taxonomy->category_id)->keyBy('id')
            ]
        ]);
    }

    public function getTaxonomiesByCptId($cpd_id) {
        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $taxonomies = $taxonomyHandler->getTaxonomies($cpd_id);
        if($taxonomies == null) return;

        foreach($taxonomies as $taxonomy) {
            $taxonomy->name = xe_trans($taxonomy->name);
            $items = $taxonomyHandler->getCategoryItemsTree($taxonomy->id)->toArray();
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
            $login_token = $request->get('login_token', '');
            $user_id = $request->get('user_id', '');

            //소셜로그인을 통한 최초 토큰로그인시 단한번 저장을 해준다
            if($login_token != '' && $user_id != ''){
                $token_info = AhUserToken::where('device_id',$request->header('X-AMUZ-DEVICE-UUID'))->where('user_id',$user_id)->where('token',$login_token)->first();
            }else{
                $token_info = AhUserToken::where('device_id',$request->header('X-AMUZ-DEVICE-UUID'))->where('token',$request->header('X-AMUZ-REMEMBER-TOKEN'))->first();
            }

            if($token_info == null){
                $retObj->addError('ERR_BROKEN_SESSION','잘못된 토큰이 전달되었습니다.');
            }else{
                $user = User::find($token_info->user_id);
                $this->auth->login($user);

                $deviceInfo = $retObj->get('deviceInfo');
                //같은기기의 푸시토큰 업데이트
                $token_info->push_token = $deviceInfo['push_token'];
                $token_info->save();

                //샌드버드플러그인이 설치되어있으면 토큰정보를 업데이트 해 준다.
                $this->updateSendbirdToken($token_info);

                $retObj->setMessage("로그인에 성공하였습니다.");
                $retObj->set('user',$this->arrangeUserInfo($user,$request));
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
            $retObj = $this->doLogin($request, $retObj,$user);
        }else{
            $retObj->addError('ERR_AccountNotFoundOrDisabled',xe_trans('xe::msgAccountNotFoundOrDisabled'));
        }
        return $retObj->output();
    }

    public function socialLogin(Request $request,Handler $socialLoginHandler,Socialite $socialite, $provider){
        //로그인 세션 있을 경우 제거
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $retObj = $this->checkDeviceConnect($request);

        $requestHeader = base64_decode($request->header('postData'));
        $postData = json_dec($requestHeader,true);
        $retObj->set('provider',$provider);

        $authedUser = json_dec(array_get($postData,'user'),true);
        $authedToken = json_dec(array_get($postData,'token'),true);

        switch($provider){
            case "kakao" :
                $is_email_valid = Arr::get($authedUser, 'kakao_account.is_email_valid');
                $is_email_verified = Arr::get($authedUser, 'kakao_account.is_email_verified');

                $userContract = (new \Laravel\Socialite\Two\User())->setRaw($authedUser)->map([
                    'id'        => $authedUser['id'],
                    'nickname'  => Arr::get($authedUser, 'properties.nickname'),
                    'name'      => Arr::get($authedUser, 'properties.nickname'),
                    'email'     => $is_email_valid && $is_email_verified ? Arr::get($authedUser, 'kakao_account.email') : null,
                    'avatar'    => Arr::get($authedUser, 'properties.profile_image'),
                ]);
                $userContract->setToken(Arr::get($authedToken, 'access_token'))
                    ->setRefreshToken(Arr::get($authedToken, 'refresh_token'))
                    ->setExpiresIn(Arr::get($authedToken, 'access_token_expires_at'));
                break;
            case "apple" :
                $savedAppleInfo = AhUserAppleInfo::find($authedUser["user_id"]);
                if($savedAppleInfo !== null){
                    $savedAppleInfo->user = json_enc($authedUser);
                    $savedAppleInfo->save();
                }else{
                    $savedAppleInfo = new AhUserAppleInfo();
                    $savedAppleInfo->id = $authedUser["user_id"];
                    $savedAppleInfo->user = json_enc($authedUser);
                    $savedAppleInfo->save();
                }

                $providerInstance = $socialite->driver($provider);
                $providerInstance->stateless();

                if (array_key_exists("firstName", $authedUser) && array_key_exists("lastName", $authedUser)) {
                    $firstName = $authedUser["firstName"];
                    $lastName = $authedUser["lastName"];
                    $fullName = trim(
                        ($firstName ?? "")
                        . " "
                        . ($lastName ?? "")
                    );
                } else if(array_key_exists("firstName", $authedUser)) {
                    $firstName = $authedUser["firstName"];
                    $fullName = trim(
                        ($firstName ?? "")
                    );
                } else if(array_key_exists("lastName", $authedUser)) {
                    $lastName = $authedUser["lastName"];
                    $fullName = trim(
                        ($lastName ?? "")
                    );
                }

                $userContract = (new \Laravel\Socialite\Two\User())
                    ->setRaw($authedUser)
                    ->map([
                        "id" => $authedUser["user_id"],
                        "name" => $fullName ?? null,
                        "email" => $authedUser["email"] ?? null,
                    ]);

                $userContract->setToken(Arr::get($authedToken, 'access_token'));
                break;
            case "google" :
                $userContract = (new \Laravel\Socialite\Two\User())->setRaw($authedUser)->map([
                    'id'        => $authedUser['id'],
                    'nickname'  => Arr::get($authedUser, 'displayName'),
                    'name'      => Arr::get($authedUser, 'displayName'),
                    'email'     => Arr::get($authedUser, 'email'),
                    'avatar'    => Arr::get($authedUser, 'photoUrl'),
                ]);
                $userContract->setToken(Arr::get($authedToken, 'access_token'));
                break;
        }

        if (app('xe.config')->getVal('user.register.joinable') === false) {
            return redirect()->back()->with(
                ['alert' => ['type' => 'danger', 'message' => xe_trans('xe::joinNotAllowed')]]
            );
        }

        //social 계정 이메일 같은경우 연결
        $userAccount = $socialLoginHandler->getRegisteredUserAccount($userContract, $provider);
        if($userAccount == null && $userContract->email){
            $signedUser = XeUser::where('email',$userContract->email)->first();
            if($signedUser != null){
                try {
                    $socialLoginHandler->connectAccount($signedUser, $userContract, $provider);
                } catch (ExistsAccountException $e) {
                    $this->throwHttpException(xe_trans('social_login::alreadyRegisteredAccount'), 409, $e);
                }

                $userAccount = $socialLoginHandler->getRegisteredUserAccount($userContract, $provider);
            }
        }

        if ($userAccount !== null) {
            $user = $userAccount->user;
            $retObj = $this->doLogin($request, $retObj, $user);
            return redirect()->route('ah::closer',['remember_token'=>$retObj->get('remember_token'),'user'=>$retObj->get('user')]);
        }
        //가입된 계정이 없을 경우 회원가입
        if (app('xe.config')->getVal('social_login.registerType', 'simple') === 'step' &&
            $socialLoginHandler->checkNeedRegisterForm($userContract) === false) {

            $userData = [
                'email' => $userContract->getEmail(),
                'contract_email' => $userContract->getEmail(),
                'display_name' => $userContract->getNickname() ?: $userContract->getName(),
                'account_id' => $userContract->getId(),
                'provider_name' => $provider,
                'token' => $userContract->token,
                'token_secret' => $userContract->tokenSecret ?? ''
            ];

            XeDB::beginTransaction();
            try {
                $user = $socialLoginHandler->registerUser($userData);
            } catch (ExistsAccountException $e) {
                XeDB::rollback();
                $this->throwHttpException(xe_trans('social_login::alreadyRegisteredAccount'), 409, $e);
            } catch (ExistsEmailException $e) {
                XeDB::rollback();
                $this->throwHttpException(xe_trans('social_login::alreadyRegisteredEmail'), 409, $e);
            } catch (\Throwable $e) {
                XeDB::rollback();
                throw $e;
            }
            XeDB::commit();

            $retObj = $this->doLogin($request, $retObj, $user);
            return redirect()->route('ah::closer',['remember_token'=>$retObj->get('remember_token'),'user'=>$retObj->get('user')]);
        }

        $request->session()->put('userContract', $userContract);
        $request->session()->put('provider', $provider);

        return redirect()->route('ahib::user_register');
    }

    protected function checkDeviceConnect(Request $request){
        $retObj = new BaseObject();
        $deviceInfo = [
            'device_name' => $request->header('X-AMUZ-DEVICE-NAME'),
            'device_version' => $request->header('X-AMUZ-DEVICE-VERSION'),
            'device_id' => $request->header('X-AMUZ-DEVICE-UUID')
        ];
        foreach($deviceInfo as $key => $val){
            if($val == null){
                $retObj->addError('ERR_NONE_ALLOW',sprintf('허용되지 않은 접근입니다. %s가 필요합니다.',$key));
                return $retObj->output();
            }
        }

        if($request->hasHeader('X-AMUZ-PUSH-TOKEN')) $deviceInfo['push_token'] = $request->header('X-AMUZ-PUSH-TOKEN');
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

    public function bannerItemData(Request $request) {
        $this->validate($request, [
            'item_id' => 'required'
        ]);

        $item_id = $request->get('item_id');
        $bannerHandler = app('xe.banner');
        $item = $bannerHandler->getItem($item_id);

        return XePresenter::makeApi(['item' => $item]);
    }

//    이것은 마치 나의 필살기 by xiso
    public function syncDocuments(Request $request, ConfigHandler $boardConfigHandler, CptModuleConfigHandler $cptModuleConfigHandler){
        $target_slug = $request->get('target_slug');
        if(!$target_slug){
            $target_instances = $request->get('target_instances','[]');
            $target_instances = json_dec($target_instances) ?? $request->get('target_instances');
        }else{
            $target_instance = new \stdClass();
            $target_instance->slug = $target_slug;
            $target_instance->last_updated_at = "1970-01-01 00:00:00";
            $target_instances = [$target_instance];
        }
        $site_key = \XeSite::getCurrentSiteKey();

        //호출 가능성이 있는 모든 텍소노미를 동기화한다.
        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $archives = [];
        $returnDocuments = [];

        foreach($target_instances as $target_instance){
            $menu = MenuItem::where('url',$target_instance->slug)->first();
            if(!$menu) continue;

            $model = null;
            switch($menu->type){
                case "board@board" :
                    $config = $boardConfigHandler->get($menu->id);
                    $model = Board::division($menu->id);
                    $model = $model->where('instance_id', $config->get('boardId'));

                    //TODO Board Category 들어올때 대응해야함. 준비안되있음 ^^* 보드카테고리 동작하면 에러날거임 :) 분명.
                    break;
                case "cpt@cpt" :
                    $config = $cptModuleConfigHandler->get($menu->id);
                    $model = CptDocument::division($config->get('cpt_id'), $site_key);
                    $model = $model->where('instance_id', $config->get('cpt_id'));

                    $archive = $taxonomyHandler->getTaxonomies($config->get('cpt_id'));
                    foreach($archive as $taxonomy){
                        $archives[$taxonomy->extra->slug] = $taxonomy;
                        $archives[$taxonomy->extra->slug]->items = $taxonomyHandler->getCategoryItemAttributes($taxonomy->id)->keyBy('id');
                    }
                    break;
                default :
                    break;
            }
            $model = $model->where('site_key', $site_key)->where('updated_at', '>=', $target_instance->last_updated_at);
            $documents = $model->get();

            //텍소노미를 붙이기 전에 부모 <-> 자식간의 데이터를 오버라이드 하여 싱크해준다.
            $archives = arrangeTaxonomyItemsOverride($archives);

            //arrange
            foreach($documents as $document){
                $selectedTaxonomyItems = $taxonomyHandler->getItemOnlyTargetId($document->id);
                $result = [];
                foreach($selectedTaxonomyItems as $taxonomyItem) {
                    $category_Extra = CategoryExtra::where('category_id', $taxonomyItem->category_id)->first();
                    $taxonomyItem->slug = $category_Extra->slug;

                    $archive = $archives[$taxonomyItem->slug];
                    $taxonomyItem->archive_title = $archive->name;
                    if(!isset($result[$taxonomyItem->slug])) $result[$taxonomyItem->slug] = [];
                    $result[$taxonomyItem->slug][] = array_merge((array)$taxonomyItem,$archive->items[$taxonomyItem->id]);
                }
//                $document->selectedTaxonomyItems = json_encode($result);
                $document->selectedTaxonomies = json_encode($result);
                //fortest
//                unset($document->content,$document->pure_content);
            }
            $returnDocuments[$menu->url] = $documents ?: [];
        }
        return XePresenter::makeApi([
            'last_updated_server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'documents' => $returnDocuments ?: []
        ]);
    }

    public function userList(Request $request) {
        $targetUserIds = $request->get('target_user_ids', '');
        $userGroupId = $request->get('group_id', '');
        $perPage = $request->get('perPage', 30);
        $page = $request->get('page', 1);

        $query = XeUser::where('status', 'activated');

        if($request->get('user_ids','') != ''){
            $userIds = is_array($request->get('user_ids')) ? $request->get('user_ids') : json_dec($request->get('user_ids'));
            $query->whereIn('id', $userIds);
        }

        //그룹찾기
        if($userGroupId !== '' && $userGroupId != 'user') {
            $query->whereHas('groups', function($q) use ($userGroupId){
                $q->where('group_id',$userGroupId);
            });
        }

        if($request->get('exclude_auth','N') == "Y"){
            $query->where('id', '!=', $this->auth->user()->id);
        }

        if($request->get('near_target','') != '' && $request->get('lat','') != '' && $request->get('lng','') != ''){
            $near = [
                'field_id' => $request->get('near_target'),
                'lat' => $request->get('lat'),
                'lng' => $request->get('lng'),
                'group' => $request->get('group_id'),
                'limit_distance' => $request->get('limit_distance',20),
            ];

//            if($near['group'] != 'user'){
                $query->join('field_dynamic_field_extend_location as types_' . $near['field_id'], function ($join) use ($near){
                    $join->on('user.id', '=', 'types_'.$near['field_id'] . '.target_id')
                        ->where('types_'.$near['field_id'] . '.group',$near['group'])
                        ->where('types_'.$near['field_id'] . '.field_id',$near['field_id']);
                });
//            }

            $haversine = "(6371 * acos(cos(radians(" . $near['lat'] . "))
                    * cos(radians(`xe_types_".$near['field_id']."`.`lat`))
                    * cos(radians(`xe_types_".$near['field_id']."`.`lng`)
                    - radians(" . $near['lng'] . "))
                    + sin(radians(" . $near['lat'] . "))
                    * sin(radians(`xe_types_".$near['field_id']."`.`lat`))))";

            $query->select("*")
                ->selectRaw("{$haversine} AS distance")
                ->whereRaw("{$haversine} < ?", [$near['limit_distance']]);
        }

        if($request->get('only_has_profile','N') == "Y"){
            $query->whereNotNull("profile_image_id");
        }

        if($request->get('search_taxonomy','') != ''){
            //{allow_category_item_item_id: [313, 312, 311, 309, 351, 349], current_status_item_id: [], gender_boolean: []}
            $searchTargets = json_dec($request->get('search_taxonomy','[]'));

            foreach($searchTargets as $fieldId => $fieldInfo){
                $fieldType = $fieldInfo[0];
                $selectedValues = $fieldInfo[1];
                switch($fieldType){
                    case "category" :
                        foreach($selectedValues as $val){
                            $query->where($fieldId . '.item_id','like',"%" . $val . "%");
                        }
                        break;
                    case "boolean" :
                        $query->where(function($q) use ($fieldId,$selectedValues){
                            foreach($selectedValues as $val){
                                $q->orWhere($fieldId . '.boolean',$val);
                            }
                        });
                    break;
                }

            }
        }

        $query->with('groups');
        $this->makeOrder($query, $request);

        $paginate = $query->paginate($perPage, ['*'], 'page', $page);
        $total = $paginate->total();
        $currentPage = $paginate->currentPage();
        $count = 0;

        $userList = $paginate->getCollection()->keyBy('id');

        foreach($userList as $key => $user) $userList[$key] = $this->arrangeUserInfo($user,$request);
        $paginate->setCollection($userList);

        // 순번 필드를 추가하여 transform
        $paginate->getCollection()->transform(function ($paginate) use ($total, $perPage, $currentPage, &$count) {
            $paginate->seq = ($total - ($perPage * ($currentPage - 1))) - $count;
            $count++;
            return $paginate;
        });

        return XePresenter::makeApi(['error' => 0, 'message' => 'Complete', 'paginate' => $paginate]);
    }

    public function user_groups() {
        $userGorups = UserGroup::get()->keyBy('name');
        return $userGorups;
    }

    //간단하게 스스로의 정보를 받은필드찾아서 업데이트한다.
    public function userUpdate(Request $request){
        $loggedUser = $this->auth->user();

        $inputs = $request->except('_token');
        $target_update_datas = [];
        foreach($inputs as $key => $val){
//            if(!isset($loggedUser->{$key})) continue;
            $target_update_datas[$key] = $val;
        }
        $result = $loggedUser->update($target_update_datas);

        $retObj = new BaseObject();
        $retObj->set('updated',$target_update_datas);
        $retObj->set('result',$result);

        return $retObj->output();
    }

    public function makeOrder($query, $request)
    {
        $orderType = $request->get('order_type', '');

        if ($orderType == '') {
            // order_type 이 없을때만 dyFac Config 의 정렬을 우선 적용한다.
            $orders = $request->get('orders', []);
            foreach ($orders as $order) {
                $arr_order = explode('|@|',$order);
                $query->orderBy($arr_order[0], $arr_order[1]);
            }

        } elseif ($orderType == 'display_name_asc') {
            $query->orderBy('display_name', 'asc');
        } elseif ($orderType == 'display_name_desc') {
            $query->orderBy('display_name', 'desc');
        } elseif ($orderType == 'email_asc') {
            $query->orderBy('email', 'asc');
        } elseif ($orderType == 'email_desc') {
            $query->orderBy('email', 'desc');
        } elseif ($orderType == 'login_id_asc') {
            $query->orderBy('login_id', 'asc');
        } elseif ($orderType == 'login_id_desc') {
            $query->orderBy('login_id', 'desc');
        } elseif ($orderType == 'created_at_asc') {
            $query->orderBy('created_at', 'asc');
        } elseif ($orderType == 'created_at_desc') {
            $query->orderBy('created_at', 'desc');
        }
        $query->getProxyManager()->orders($query->getQuery(), $request->all());

        return $query;
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
    }

    private function doLogin($request, $retObj, $user){
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

                $delete = AhUserToken::where('device_id' ,$deviceInfo['device_id'])->delete();
                $user_token = AhUserToken::firstOrNew(['device_id' => $deviceInfo['device_id'], 'user_id' => $user->id]);
                foreach($deviceInfo as $key => $val) $user_token->{$key} = $val;

                $user_token->save();

                //샌드버드플러그인이 설치되어있으면 토큰정보를 업데이트 해 준다.
                $this->updateSendbirdToken($user_token);

                $retObj->setMessage("로그인에 성공하였습니다.");
                $retObj->set('user',$this->arrangeUserInfo($user,$request));
                $retObj->set('remember_token',$token);
                break;
        }

        //로그인 시점에 샌드버드와 회원정보 동기화
        if(Schema::hasTable('sendbird_user_token')){
            $sendBirdChatApp = app('amuz.sendbird.chat');
            $sendBirdChatApp->syncUserData($user->id);
        }

        return $retObj;
    }

    private function arrangeUserInfo($user,$request){
        $user->addVisible('email');
        $user->addVisible('login_id');

        $user_groups = $user->groups;
        $user->setRelation('groups', $user->groups->keyBy('id'));
        $user->addVisible('groups');

        if($request->get('near_target','') != '' && $request->get('lat','') != '' && $request->get('lng','') != ''){
            $user->addVisible('field_id');
            $user->addVisible('distance');
        }

        foreach ($user_groups as $user_group) {
            $user_group->fieldTypes = $this->xeDynamicField->gets($user_group->id);
            $fieldValues = [];

            // 그룹별 필드 데이터 불러와서 넣기
            $dummy = $this->userTypeHandler->getDynamicFieldData($user_group->fieldTypes, $user_group->id, $user->id);

            foreach($dummy as $key => $val){
                $fieldValues[$key] = $val;
                $user->$key = $val;
                $user->addVisible($key);
            }
            $user_group->fieldValues = $fieldValues;
        }

        if(Schema::hasTable('sendbird_user_token')){
            $sendBirdChatApp = app('amuz.sendbird.chat');
            $user->sendBird = $sendBirdChatApp->getSbToken($user->id);
            $user->addVisible("sendBird");
        }
        return $user;
    }

    /**
     * throw http exception
     *
     * @param string $msg      massage
     * @param null   $code     code
     * @param null   $previous previous
     *
     * @return void
     * @throws HttpXpressengineException
     */
    protected function throwHttpException($msg, $code = null, $previous = null)
    {
        $e = new HttpXpressengineException([], $code, $previous);
        $e->setMessage($msg);

        throw $e;
    }
    public function isJson($string) {
        return ((is_string($string) &&
            (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }
}
