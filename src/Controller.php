<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\Models\AhUserToken;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Symfony\Component\HttpKernel\Exception\HttpException;
use XeFrontend;
use XePresenter;
use Schema;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Keygen\Keygen;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Plugins\Board\ConfigHandler;
use Xpressengine\Plugins\Board\Models\Board;
use Xpressengine\Plugins\Board\Services\BoardService;
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
                    $menu->create_url = route('ahib::board_create',['instance_id' => $menu->id]);
                    break;
                case "cpt@cpt" :
                    $config = $cptModuleConfigHandler->get($menu->id);
                    $taxonomyHandler = app('overcode.df.taxonomyHandler');
                    $taxonomies = $taxonomyHandler->getTaxonomies($config->get('cpt_id'));
                    $categories = [];

                    foreach($taxonomies as $taxonomy) {
                        $categories[$taxonomy->extra->slug]['group'] = $taxonomyHandler->getTaxFieldGroup($taxonomy->id);
                        //TODO : 언젠가 필요하면 확장필드를 붙여주자 :)
//                        $categories[$taxonomy->extra->slug]['items'] = $taxonomyHandler->getCategoryItemAttributes($taxonomy->id,$categories[$taxonomy->extra->slug]['group']);
                        $categories[$taxonomy->extra->slug]['items'] = $taxonomyHandler->getCategoryItemsTree($taxonomy->id,$categories[$taxonomy->extra->slug]['group']);
                    }
                    $menu->categories = $categories;

                    $menu->create_url = route('ahib::cpt_create',['cpt_id'=>$config->get('cpt_id')]);
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
            $token_info = AhUserToken::where('device_id',$request->header('X-AMUZ-DEVICE-UUID'))->where('token',$request->header('X-AMUZ-REMEMBER-TOKEN'))->first();
            if($token_info == null){
                $retObj->addError('ERR_BROKEN_SESSION','잘못된 토큰이 전달되었습니다.');
            }else{
                $user = User::find($token_info->user_id);
                $this->auth->login($user);

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
                    $retObj->set('user',$this->arrangeUserInfo($user,$request));
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
        $target_instances = $request->get('target_instances','[]');
        $target_instances = json_dec($target_instances);
        $site_key = \XeSite::getCurrentSiteKey();

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
                    break;
                case "cpt@cpt" :
                    $config = $cptModuleConfigHandler->get($menu->id);
                    $model = CptDocument::division($config->get('cpt_id'), $site_key);
                    $model = $model->where('instance_id', $config->get('cpt_id'));
                    break;
                default :
                    break;
            }
            $model = $model->where('site_key', $site_key)->where('updated_at', '>=', $target_instance->last_updated_at);
            $documents = $model->get();
            $returnDocuments[$menu->url] = $documents ?: [];
        }
        return XePresenter::makeApi([
            'last_updated_server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'documents' => $returnDocuments ?: []
        ]);
    }

    public function userList(Request $request) {
        $userGroupId = $request->get('group_id', '');
        $perPage = $request->get('perPage', 30);
        $page = $request->get('page', 1);

        $query = XeUser::where('status', 'activated');

        //그룹찾기
        if($userGroupId !== '') {
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
                'limit_distance' => $request->get('limit_distance',20),
            ];
            $query->join('field_dynamic_field_extend_location', function ($join) use ($near){
                $join->on('user.id', '=', 'field_dynamic_field_extend_location.target_id')
                    ->where('field_dynamic_field_extend_location.field_id',$near['field_id']);
            });

            $haversine = "(6371 * acos(cos(radians(" . $near['lat'] . "))
                    * cos(radians(`lat`))
                    * cos(radians(`lng`)
                    - radians(" . $near['lng'] . "))
                    + sin(radians(" . $near['lat'] . "))
                    * sin(radians(`lat`))))";

            $query->select("*")
                ->selectRaw("{$haversine} AS distance")
                ->whereRaw("{$haversine} < ?", [$near['limit_distance']]);
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

    private function arrangeUserInfo($user,$request){
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
}
