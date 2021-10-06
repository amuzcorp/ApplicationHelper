<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\Plugin as Plugin;
use App\Facades\XeFrontend;
use App\Facades\XePresenter;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Http\Request;
use Xpressengine\Menu\Models\Menu;

class SettingsController extends BaseController
{

    public function __construct()
    {
        XePresenter::share('title','앱 설정');
        XePresenter::share('description','어플리케이션 설정입니다.');
    }

    public function index()
    {
        // load css file
        XeFrontend::css(Plugin::asset('assets/style.css'))->load();

        $resources = \Route::getRoutes();
        $_routes = [];
        $_instance_routes = [];
        $skeep_as = [
            'proSEO::',
            'editor',
            'draft',
            'widgetbox',
        ];
        foreach($resources->getRoutes() as $id => $route){
            $middleware = is_array(array_get($route->action,'middleware',[])) ? array_get($route->action,'middleware',[]) : [];
            if(in_array('settings', $middleware)) continue;
            if(array_get($route->action,'prefix') == "_debugbar") continue;

            $is_skip = false;
            foreach($skeep_as as $keep_keyword) if(strstr(array_get($route->action,'as'),$keep_keyword)) $is_skip = true;
            if($is_skip) continue;

            $route->as = array_get($route->action,'as');
            $route->use_method = array_get($route->action,'uses',array_get($route->action,'controller'));
            $route->use_method = is_callable($route->use_method) ? "Closer" : $route->use_method;

            $key = str_replace(".","_",array_get($route->action,'as'));

            $module = array_get($route->action,'module');
            if($module == null){
                $_routes[$key] = $route;
            }else{
                if(!isset($_instance_routes[$module])) $_instance_routes[$module] = [];
                $_instance_routes[$module][$key] = $route;
            }
        }

        ksort($_routes);
        ksort($_instance_routes);

        $method_colors = [
            'GET' => 'primary',
            'POST' => 'info',
            'PUT' => 'success',
            'DELETE' => 'danger',
            'HEAD' => 'dark'
        ];
        // output
        return XePresenter::make('ApplicationHelper::views.settings.index', compact('_routes','_instance_routes','method_colors'));
    }

    public function navigator()
    {
        $menus = Menu::where('site_key',\XeSite::getCurrentSiteKey())->get();

        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        $deliver_menus = $ah_config->get('navigator');

        // output
        return \XePresenter::make('ApplicationHelper::views.settings.navigator', compact('menus','deliver_menus'));
    }

    public function saveConfig(Request $request, $type){
        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        if($ah_config == null) $xe_config->set('application_helper',[]);

        switch($type){
            case "navigator" :
                    $keys = $request->get('keys');
                    $menus = $request->get('menus');
                    $deliverMenus = [];
                    foreach($keys as $key => $menuKeyId) $deliverMenus[$menuKeyId]  = $menus[$key];

                    $xe_config->setVal('application_helper.navigator',$deliverMenus);
                break;
        }

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }
}
