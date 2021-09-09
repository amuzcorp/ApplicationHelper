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

                Route::get('/permission/list', ['as' => 'ah::config_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getPermission']);
                Route::get('/config/list', ['as' => 'ah::config_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getConfig']);
                Route::get('/keychain/list', ['as' => 'ah::keychain','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@get_keychain']); // with keychain plugin
                Route::get('/video/vimeo/list', ['as' => 'ah::video_list','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@get_videos_vimeo']); // with vimeo video plugin

                Route::get('/instance/list', ['as' => 'ah::instance_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getInstance']);

                Route::get('/lang/{locale?}', ['as' => 'ah::lang_list', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@getLang']);

                Route::get('/user/register/{group_id?}', ['as' => 'ah::user_register', 'uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@userRegister']);

                //use API Controller
                Route::post('/auth/login',['as' => 'ah::post_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@postLogin']);
                Route::post('/auth/token',['as' => 'ah::token_login','uses' => 'Amuz\XePlugin\ApplicationHelper\Controller@tokenLogin']);
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
