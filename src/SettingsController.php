<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\Plugin as Plugin;
use App\Facades\XeFrontend;
use App\Facades\XePresenter;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Sections\SkinSection;
use Illuminate\Support\Str;
use Xpressengine\Http\Request;
use Xpressengine\Menu\Models\Menu;
use Xpressengine\Plugins\Banner\Models\Group;
use Xpressengine\Routing\InstanceRoute;
use Xpressengine\Plugins\Banner\Handler as BannerHandler;

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
            'HEAD' => 'dark',
            'PATCH' => 'warning',
            'OPTIONS' => 'secondary',
        ];
        // output
        return XePresenter::make('ApplicationHelper::views.settings.index', compact('_routes','_instance_routes','method_colors'));
    }

    public function navigator()
    {
        $menus = Menu::where('site_key',\XeSite::getCurrentSiteKey())->get();

        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        if($ah_config == null){
            $xe_config->set('application_helper',[]);
            $ah_config = $xe_config->get('application_helper');
        }

        $deliver_menus = $ah_config->get('navigator',[]);

        // output
        return \XePresenter::make('ApplicationHelper::views.settings.navigator', compact('menus','deliver_menus'));
    }

    public function instances()
    {
        $instances = InstanceRoute::where('site_key',\XeSite::getCurrentSiteKey())->get();

        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        if($ah_config == null){
            $xe_config->set('application_helper',[]);
            $ah_config = $xe_config->get('application_helper');
        }

        $instance_configs = $ah_config->get('instances');

        // output
        return \XePresenter::make('ApplicationHelper::views.settings.instances', compact('instances','instance_configs'));
    }

    /**
     * edit Skin setting
     *
     * @return \Xpressengine\Presenter\Presentable
     */
    public function inAppBrowserSkins()
    {
        $skinSections = [
            "auth" => [
                    'title' => xe_trans('xe::userSingUpLoginSkin'),
                    'skin_id' => 'ahib/user/auth',
                ],
            "user" => [
                    'title' => xe_trans('xe::myPageSkin'),
                    'skin_id' => 'ahib/user/settings',
                ],
            "board" => [
                    'title' => xe_trans('board::board'),
                    'skin_id' => 'ahib/board',
                ],
            "cpt" => [
                    'title' => '사용자 정의 문서',
                    'skin_id' => 'ahib/cpt',
                ],
        ];
        foreach($skinSections as $section_id => $section){
            $skinSection = new SkinSection($section['skin_id']);
            $skinSections[$section_id]['skinSection'] = $skinSection->render();
        }

        return XePresenter::make(
            'ApplicationHelper::views.settings.ibSkin',
            compact('skinSections')
        );
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

                    $xe_config->setVal('application_helper.'.$type,$deliverMenus);
                break;

            case "instances" :
                    $skins = $request->get('skin');
//                    $states = $request->get('state');

                    $options = [];
                    foreach($skins as $instance_id => $skin){
                        $options[$instance_id] = [
                            'skin' => $skin,
//                            'state' => $states[$instance_id],
                        ];
                    }

                    $xe_config->setVal('application_helper.'.$type,$options);
                break;
            case "banner" :
                $keys = $request->get('keys');
                $groups = $request->get('groups');
                $slide_time = $request->get('slide_time');
                $bannerGroup = [];
                foreach($keys as $key => $groupKeyId) {
                    $bannerGroup[$groupKeyId]  = [
                        'group' => $groups[$key],
                        'slide_time' => $slide_time[$key]
                    ];
                }
                $xe_config->setVal('application_helper.'.$type,$bannerGroup);

                break;
        }

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function banner_config_index(BannerHandler $handler, Request $request) {
        $title = '어플리케이션 배너 설정';
        $description = '어플리케이션 배너 설정입니다';

        // set browser title
        XeFrontend::title($title);

        $xe_config = app('xe.config');
        $ah_config = $xe_config->get('application_helper');
        if($ah_config == null){
            $xe_config->set('application_helper',[]);
            $ah_config = $xe_config->get('application_helper');
        }
        $ah_banner_groups = $ah_config->get('banner',[]);
        $groups = app('xe.banner')->getGroups();

        $banner_group = $handler->getGroups();
        $app_config = app('xe.config')->get('application_helper.app_config');
        if(!$app_config->get('groups') || count($app_config->get('groups')) <= 0) {
            $app_config['groups'] = [
                ['id' => 'default', 'title' => '기본']
            ];

            app('xe.config')->set('application_helper.app_config', [
                'groups' => $app_config['groups']
            ]);
        }
//
//        $menus = Menu::where('site_key',\XeSite::getCurrentSiteKey())->get();
//        $main_banner = $app_config->get('banner_list');
//        $idx = 0;
//        $str = Str::random(8);
//        foreach($main_banner as $key => $banner_item) {
//            $idx++;
//            $item = $this->setBannerOptions($banner_item);
//
//            if(!isset($item['idx']) || $item['idx'] === '') {
//                $item['idx'] = $str.'_'.$idx;
//            }
//
//            if($item === null) {
//                unset($main_banner[$key]);
//                continue;
//            }
//
//            $menus = Menu::where('site_key',\XeSite::getCurrentSiteKey())->get();
//            if(!isset($banner_item['menu'])) $item['menu'] = $menus[0]->id;
//            else if($banner_item['menu'] === '') $item['menu'] = $menus[0]->id;
//            else $item['menu'] = $banner_item['menu'];
//            $main_banner[$key] = $item;
//        }
//
//        $sortArr = array();
//        foreach($main_banner as $res) $sortArr [] = $res['menu'];
//        array_multisort($sortArr , SORT_ASC, $main_banner);
//
//        $content_banner = $app_config->get('content_banner_list');
//        foreach($content_banner as $key => $banner_item) {
//            $item = $this->setBannerOptions($banner_item);
//            $idx++;
//
//            if($item === null) {
//                unset($content_banner[$key]);
//                continue;
//            }
//
//            if(!isset($item['idx'])) {
//                $item['idx'] = $str.'_'.$idx;
//            }
//
//            $menus = Menu::where('site_key',\XeSite::getCurrentSiteKey())->get();
//            if(!isset($banner_item['menu'])) $item['menu'] = $menus[0]->id;
//            else if($banner_item['menu'] === '') $item['menu'] = $menus[0]->id;
//            else $item['menu'] = $banner_item['menu'];
//            $content_banner[$key] = $item;
//        }
//        $sortArr = array();
//        foreach($content_banner as $res) $sortArr [] = $res['menu'];
//        array_multisort($sortArr , SORT_ASC, $content_banner);
        $banner_data = [];
        foreach($ah_banner_groups as $key => $item) {
            $banner_config = $ah_banner_groups[$key];
            $group_id = $banner_config['group'];
            $group = Group::find($group_id);
            $banner_items = $handler->getItems($group);
            foreach($banner_items as $banner) {
                if($banner->image === "") continue;
                $banner->slide_time = (int) $banner_config['slide_time'];
                $banner_data[$key][] = $banner->toArray();
            }
        }
        app('xe.config')->set('application_helper.app_config', [
            'ah_banner_config' => $banner_data
        ]);

//        dd(app('xe.config')->get('application_helper.app_config'));

        // output
        return XePresenter::make('ApplicationHelper::views.settings.banner.index',
            compact('title', 'description', 'banner_group', 'groups', 'ah_banner_groups'));
        return XePresenter::make('ApplicationHelper::views.settings.banner.index',
            compact('title', 'description', 'banner_group', 'groups', 'ah_banner_groups',
                'app_config', 'main_banner', 'content_banner', 'app_banner_groups', 'menus', 'str'));
    }

    public function config_update(Request $request) {
        $configs = json_dec($request->get('banner_list'));
        $content_banner = json_dec($request->get('content_banner_list'));
        app('xe.config')->set('application_helper.app_config', [
            'banner_list' => $configs,
            'content_banner_list' => $content_banner
        ]);
        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function setBannerOptions($item) {
        $bannerHandler = app('xe.banner');
        $banner = $bannerHandler->getItem($item['id']);
        if(!$banner) return null;
        if(!isset($banner->image)) {
            $imagePath = '';
            $imageID = '';
        } else {
            if($banner->image !== '') {
                $imagePath = $banner->image['path'];
                $imageID = $banner->image['id'];
            } else {
                $imagePath = '';
                $imageID = '';
            }
        }
        $item['id'] = $banner->id;
        $item['group_id'] = $banner->group_id;
        $item['title'] = $banner->title;
        $item['image_path'] = $imagePath;
        $item['image_id'] = $imageID;
        $item['created_at'] = $banner->created_at;
        $item['slide_time'] = (int) $item['slide_time'];
        $item['content'] = $banner->content;
        $item['link'] = $banner->link;
        $item['link_target'] = $banner->link_target;
        $item['group'] = $banner->group;

        return $item;
    }

}
