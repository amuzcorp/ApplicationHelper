<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Schema;
use Amuz\XePlugin\ApplicationHelper\Middleware\AmuzApiHelpers;
use Amuz\XePlugin\ApplicationHelper\Migrations\Migration;
use Illuminate\Support\Str;
use Route;
use Xpressengine\Http\Request;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\SocialLogin\Handler;

class Plugin extends AbstractPlugin
{
    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        // implement code
        $router = app('router');
        $router->prependMiddlewareToGroup('web', AmuzApiHelpers::class);

        $this->route();
        $this->settingsRoute();

        $this->interceptDynamicField();

        if(!app('xe.config')->get('application_helper.app_config')) {

            app('xe.config')->set('application_helper', []);
            app('xe.config')->set('application_helper.app_config', [
                'banner_list' => [],
                'content_banner_list' => []
            ]);
        } else {
            if(!app('xe.config')->get('application_helper.app_config')->get('banner_list')) {
                app('xe.config')->set('application_helper.app_config', [
                    'banner_list' => []
                ]);
            }
            if(!app('xe.config')->get('application_helper.app_config')->get('content_banner_list')) {
                app('xe.config')->set('application_helper.app_config', [
                    'content_banner_list' => []
                ]);
            }
        }

    }

    protected function route()
    {
        // implement code
        Route::fixed($this->getId(),
            function () {
                Route::get('/', [
                    'as' => 'ah::index','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@index'
                ]);

                Route::get('/csrf/_token', ['as' => 'ah::get_token', 'uses' => function(Request $request){
                    return response()->json([
                        '_token' => csrf_token()
                    ]);
                }]);
                Route::get('/closer', ['as' => 'ah::closer', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@closer']);

                //for app sync with sqlite
                Route::get('/permission/list', ['as' => 'ah::config_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getPermission']);
                Route::get('/config/list', ['as' => 'ah::config_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getConfig']);
                Route::get('/keychain/list', ['as' => 'ah::keychain','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@get_keychain']); // with keychain plugin
                Route::get('/video/vimeo/list', ['as' => 'ah::video_list','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@get_videos_vimeo']); // with vimeo video plugin

                Route::get('/lang/{locale?}', ['as' => 'ah::lang_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getLang']);

                //get lists
                Route::get('/instance/list', ['as' => 'ah::instance_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getInstance']);
                Route::get('/navigator/{menu_key}', ['as' => 'ah::navigator_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getNavigator']);
                Route::get('/taxonomies/{cpd_id}', ['as' => 'ah::taxonomy_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getTaxonomiesByCptId']);
                Route::get('/taxonomyItems/{taxonomyKey}', ['as' => 'ah::taxonomy', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getTaxonomiesByTaxonomyId']);

                //for inApp Browsers
                Route::get('/ib/user/register/{group_id?}', ['as' => 'ahib::user_register', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@getRegister']);
                Route::post('/ib/user/register/{group_id?}', ['as' => 'ahib::user_register.store', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@postRegister']); // for store
                Route::post('/ib/user/group_select', ['as' => 'ahib::post_group_select', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@postGroupSelect']); // for store
                Route::post('/ib/user/post_term_agree', ['as' => 'ahib::post_term_agree', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@postTermAgree']); // for store
                Route::get('/ib/user/findLoginId', ['as' => 'ahib::find_login', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\FindLoginIDController@getFindLoginID']); // for store
                Route::post('/ib/user/findLoginId', ['as' => 'ahib::find_login.post', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\FindLoginIDController@postFindLoginID']); // for store
                Route::get('/ib/user/reset', ['as' => 'ahib::pass_reset', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\PasswordController@getReset']); // for store
                Route::post('/ib/user/reset', ['as' => 'ahib::pass_reset.post', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\PasswordController@postReset']); // for store
                //  //post_term_agree

                Route::get('/ib/board/{instance_id}', ['as' => 'ahib::board', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\BoardController@index']);
                Route::get('/ib/board/{instance_id}/create', ['as' => 'ahib::board_create', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\BoardController@create']);
                Route::get('/ib/board/{instance_id}/edit/{id?}', ['as' => 'ahib::board_edit', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\BoardController@edit']);
                Route::post('/ib/board/{instance_id}/store', ['as' => 'ahib::board_store', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\BoardController@store']);
                Route::post('/ib/board/{instance_id}/update', ['as' => 'ahib::board_update', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\BoardController@update']);
                Route::post('/ib/board/{instance_id}/destroy/{id?}', ['as' => 'ahib::board_delete', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\BoardController@destroy']);

                Route::get('/ib/cpt/{slug}', ['as' => 'ahib::cpt', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\CptController@index']);
                Route::get('/ib/cpt/{slug}/create', ['as' => 'ahib::cpt_create', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\CptController@create']);
                Route::get('/ib/cpt/{slug}/edit/{id?}', ['as' => 'ahib::cpt_edit', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\CptController@edit']);
                Route::post('/ib/cpt/{slug}/store', ['as' => 'ahib::cpt_store', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\CptController@store']);
                Route::post('/ib/cpt/{slug}/update', ['as' => 'ahib::cpt_update', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\CptController@update']);
                Route::post('/ib/cpt/{slug}/destroy/{id?}', ['as' => 'ahib::cpt_delete', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\CptController@destroy']);

                //get Board Comment Data
                Route::get('/comment/getItem', ['as' => 'ah::get_comment', 'uses' => 'Amuz\XePlugin\ApplicationHelper\BoardApiController@getItem']);
                Route::get('/banner/getItem', ['as' => 'application_helper.get.banner.item', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@bannerItemData']);

                //이것은 마치 나의 필살기
                Route::any('/syncDocuments', [
                    'as' => 'documents.sync',
                    'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@syncDocuments'
                ]);

                //use API Controller
                Route::post('/auth/login',['as' => 'ah::post_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@postLogin']);
                Route::post('/auth/token',['as' => 'ah::token_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@tokenLogin']);
                Route::any('/auth/social/{provider}',['as' => 'ah::social_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@socialLogin']);

                Route::get('/auth/user_list',['as' => 'ah::user_list','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@userList']);
                Route::get('/auth/user_groups',['as' => 'ah::user_groups','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@user_groups']);

                Route::any('/auth/update',['as' => 'ah::user_update','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@userUpdate']);
        });
    }

    public function settingsRoute(){
        \XeRegister::push('settings/menu', 'setting.application_helper', [
            'title' => '애플리케이션 헬퍼',
            'description' => '어플리케이션 설정입니다',
            'display' => true,
            'ordering' => 150
        ]);
        \XeRegister::push('settings/menu', 'setting.application_helper.index', [
            'title' => 'API Routes',
            'description' => 'API 목록',
            'display' => true,
            'ordering' => 20
        ]);
        \XeRegister::push('settings/menu', 'setting.application_helper.navigator', [
            'title' => '네비게이터',
            'description' => '네비게이터 헬퍼',
            'display' => true,
            'ordering' => 50
        ]);
        \XeRegister::push('settings/menu', 'setting.application_helper.instances', [
            'title' => '인스턴스',
            'description' => '인스턴스 헬퍼',
            'display' => true,
            'ordering' => 80
        ]);
        \XeRegister::push('settings/menu', 'setting.application_helper.ibSkins', [
            'title' => '인앱브라우저',
            'description' => '인앱브라우저 라우팅 및 스킨설정',
            'display' => true,
            'ordering' => 900
        ]);
        \XeRegister::push('settings/menu', 'setting.application_helper.mobile_banner', [
            'title' => '모바일 배너설정',
            'description' => '모바일 배너 설정입니다',
            'display' => true,
            'ordering' => 150
        ]);

        Route::settings(static::getId(), function() {
            Route::group([
                'namespace' => 'Amuz\XePlugin\ApplicationHelper',
                'as' => 'application_helper.settings.'
            ], function () {
                Route::get('/index', [
                    'as' => 'index',
                    'uses' => 'SettingsController@index',
                    'settings_menu' => 'setting.application_helper.index'
                ]);
                Route::get('/navigator', [
                    'as' => 'navigator',
                    'uses' => 'SettingsController@navigator',
                    'settings_menu' => 'setting.application_helper.navigator'
                ]);
                Route::get('/instances', [
                    'as' => 'instances',
                    'uses' => 'SettingsController@instances',
                    'settings_menu' => 'setting.application_helper.instances'
                ]);
                Route::get('/inAppBrowserSkins', [
                    'as' => 'inAppBrowsers',
                    'uses' => 'SettingsController@inAppBrowserSkins',
                    'settings_menu' => 'setting.application_helper.ibSkins'
                ]);

                Route::post('/save/{type}', [
                    'as' => 'configSave',
                    'uses' => 'SettingsController@saveConfig'
                ]);

                Route::get('/settings', [
                    'as' => 'banner_config',
                    'uses' => 'SettingsController@banner_config_index',
                    'settings_menu' => 'setting.application_helper.mobile_banner'
                ]);
                Route::post('/settings/banner_config_update', [
                    'as' => 'banner_config.update',
                    'uses' => 'SettingsController@config_update',
                ]);
            });
        });
    }

    public function interceptDynamicField() {
        intercept('Xpressengine\Plugins\Board\Services\BoardService@getItem','BoardModuleShowsIntercept',function($getItem,$id,$user,$config,$isManager){
            $item = $getItem($id,$user,$config, $isManager);

            $xeMedia = app('xe.media');
            $medias = [];
            foreach($item->files as $file) {
                if ($xeMedia->is($file)) {
                    $mediaFile = $xeMedia->make($file);
                    $mediaFile->url = $mediaFile->url();
                    $medias[] = $mediaFile;
                }
            }
            $item->medias = $medias;

            if($item->tags) $item->tags_item = $item->tags->toArray();
            else $item->tags_item = [];

            //댓글 instance_id
            $comment_map = \DB::table('config')->where('name', 'comment_map')->first();

            if(!$comment_map) $comment_map = [];
            else $comment_map = json_dec($comment_map->vars, true);

            $item->comment_instance_id = '';
            if(isset($comment_map[$item->instance_id])) {
                $item->comment_instance_id = $comment_map[$item->instance_id];
            }

            //좋아요 상태 체크
            $item->has_assent = 0;
            if(app('xe.board.handler')->hasVote($item, \Auth::user(), 'assent') == true) $item->has_assent = 1;

            //찜하기 상태 체크
            $item->has_favorite = 0;
            if(app('xe.board.handler')->hasFavorite($item->id, \Auth::user()->getId()) == true) $item->has_favorite = 1;

            return $item;
        });

        intercept('Xpressengine\Plugins\Board\Services\BoardService@getItems','BoardModuleListsIntercept',function($method,$query,$request){
            $query = $method($query,$request);
            $xeMedia = app('xe.media');

            $comment_map = \DB::table('config')->where('name', 'comment_map')->first();

            if(!$comment_map) $comment_map = [];
            else $comment_map = json_dec($comment_map->vars, true);

            foreach($query as $item) {

                $medias = [];
                foreach($item->files as $file) {
                    if ($xeMedia->is($file)) {
                        $mediaFile = $xeMedia->make($file);
                        $mediaFile->url = $mediaFile->url();
                        $medias[] = $mediaFile;
                    }
                }
                $item->medias = $medias;

                //댓글 instance_id
                $item->comment_instance_id = '';
                if(isset($comment_map[$item->instance_id])) {
                    $item->comment_instance_id = $comment_map[$item->instance_id];
                }

                if($item->tags) $item->tags_item = $item->tags->toArray();
                else $item->tags_item = [];

                //좋아요 상태 체크
                $item->has_assent = 0;
                if(app('xe.board.handler')->hasVote($item, \Auth::user(), 'assent') == true) $item->has_assent = 1;

                //찜하기 상태 체크
                $item->has_favorite = 0;
                if(app('xe.board.handler')->hasFavorite($item->id, \Auth::user()->getId()) == true) $item->has_favorite = 1;
            }
            return $query;
        });

        //배너 아이템에서 이미지 교체등이 발생할 경우 자동으로 업데이트 할 목적으로 작성
        intercept('Xpressengine\Plugins\Banner\Handler@updateItem','af_update_banner_item',function($method, $item, $attrs){
            $query = $method($item, $attrs);
            if(!$query) return $query;

            //배너 아이템문서에 업데이트 발생할 경우 어댑핏 어플리케이션 배너 정보 업데이트
            $banner_list = app('xe.config')->get('application_helper.app_config')->get('banner_list');

            foreach($banner_list as $key => $banner) {
                if($banner['id'] === $query->id) {
                    $banner_list[$key]['title'] = $query->title;
                    $banner_list[$key]['group_id'] = $query->group_id;
                    $banner_list[$key]['image_path'] = $query->image['path'];
                    $banner_list[$key]['image_id'] = $query->image['id'];
                    $banner_list[$key]['content'] = $query->content;
                    $banner_list[$key]['link'] = $query->link;
                    $banner_list[$key]['link_target'] = $query->link_target;
                    $banner_list[$key]['group'] = $query->group;
                }
            }
            app('xe.config')->set('application_helper.app_config', [
                'banner_list' => $banner_list
            ]);
            return $query;
        });
    }

    /**
     * 플러그인이 활성화될 때 실행할 코드를 여기에 작성한다.
     *
     * @param string|null $installedVersion 현재 XpressEngine에 설치된 플러그인의 버전정보
     *
     * @return void
     */
    public function activate($installedVersion = null)
    {
        // implement code
    }

    /**
     * 플러그인을 설치한다. 플러그인이 설치될 때 실행할 코드를 여기에 작성한다
     *
     * @return void
     */
    public function install()
    {
        $migration = new Migration();
        $migration->up();
    }

    /**
     * 해당 플러그인이 설치된 상태라면 true, 설치되어있지 않다면 false를 반환한다.
     * 이 메소드를 구현하지 않았다면 기본적으로 설치된 상태(true)를 반환한다.
     *
     * @return boolean 플러그인의 설치 유무
     */
    public function checkInstalled()
    {
        // implement code
        $migration = new Migration();
        if(!$migration->tableExists()) return false;
        return parent::checkInstalled();
    }

    /**
     * 플러그인을 업데이트한다.
     *
     * @return void
     */
    public function update()
    {
		if(!Schema::hasTable('ah_user_apple_info')){
			$migration = new Migration();
			$migration->up();
		}
        // implement code
    }

    /**
     * 해당 플러그인이 최신 상태로 업데이트가 된 상태라면 true, 업데이트가 필요한 상태라면 false를 반환함.
     * 이 메소드를 구현하지 않았다면 기본적으로 최신업데이트 상태임(true)을 반환함.
     *
     * @return boolean 플러그인의 설치 유무,
     */
    public function checkUpdated()
    {
		if(!Schema::hasTable('ah_user_apple_info')) return false;
        // implement code

        return parent::checkUpdated();
    }
}
