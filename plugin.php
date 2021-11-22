<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\Middleware\AmuzApiHelpers;
use Amuz\XePlugin\ApplicationHelper\Migrations\Migration;
use Route;
use Xpressengine\Http\Request;
use Xpressengine\Plugin\AbstractPlugin;

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
                Route::get('/taxonomies/{taxonomy_key}', ['as' => 'ah::taxonomy_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getTaxonomies']);

                //use API Controller
                Route::post('/auth/login',['as' => 'ah::post_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@postLogin']);
                Route::post('/auth/token',['as' => 'ah::token_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@tokenLogin']);

                //for inApp Browsers
                Route::get('/ib/user/register/{group_id?}', ['as' => 'ahib::user_register', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@getRegister']);
//                Route::get('/ib/user/register/{group_id?}', ['as' => 'ahib::user_register_before', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getRegister']);
//                Route::get('/ib/user/regAfter/{group_id?}', ['as' => 'ahib::user_register', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@getRegister']);
                Route::post('/ib/user/register/{group_id?}', ['as' => 'ahib::user_register.store', 'uses' => 'Amuz\XePlugin\ApplicationHelper\InAppBrowsers\RegisterController@postRegister']); // for store
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
                    $mediaFile = app('xe.media')->make($file);
                    $mediaFile->url = $mediaFile->url();
                    $medias[] = $mediaFile;
                }
            }
            $item->medias = $medias;

            if($item->tags) $item->tags_item = $item->tags->toArray();
            else $item->tags_item = [];

            if(!app('xe.board.handler')->hasVote($item, \Auth::user(), 'assent')) $item->has_assent = 0;
            $item->has_assent = 1;

            if(!app('xe.board.handler')->hasFavorite($item->id, \Auth::user()->getId())) $item->has_favorite = 0;
            else $item->has_favorite = 1;

            return $item;
        });

        intercept('Xpressengine\Plugins\Board\Services\BoardService@getItems','BoardModuleListsIntercept',function($method,$query,$request){
            $query = $method($query,$request);
            $xeMedia = app('xe.media');

            foreach($query as $item) {

                $medias = [];
                foreach($item->files as $file) {
                    if ($xeMedia->is($file)) {
                        $mediaFile = app('xe.media')->make($file);
                        $mediaFile->url = $mediaFile->url();
                        $medias[] = $mediaFile;
                    }
                }
                $item->medias = $medias;

                if($item->tags) $item->tags_item = $item->tags->toArray();
                else $item->tags_item = [];

                if(!app('xe.board.handler')->hasVote($item, \Auth::user(), 'assent')) $item->has_assent = 0;
                $item->has_assent = 1;

                if(!app('xe.board.handler')->hasFavorite($item->id, \Auth::user()->getId())) $item->has_favorite = 0;
                else $item->has_favorite = 1;
            }
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
        // implement code

        return parent::checkUpdated();
    }
}
