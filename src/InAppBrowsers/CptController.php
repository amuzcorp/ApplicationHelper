<?php

namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\IdentifyManager;
use Overcode\XePlugin\DynamicFactory\Services\CptDocService;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use Overcode\XePlugin\DynamicFactory\Validator;
use Route;
use XePresenter;
use XeTheme;
use Xpressengine\Http\Request;
use Xpressengine\Routing\InstanceConfig;
use Overcode\XePlugin\DynamicFactory\Controllers\CptModuleController;

/**
 * Class UserController
 *
 * @category    Controllers
 * @package     App\Http\Controllers\User
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2020 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class CptController extends CptModuleController
{
    public function __construct(
        CptModuleConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler,
        DynamicFactoryDocumentHandler $dfDocHandler,
        DynamicFactoryService $dynamicFactoryService,
        IdentifyManager $identifyManager
    )
    {
        $site_key = \XeSite::getCurrentSiteKey();
        $slug = Route::current()->parameter('slug');
        $instanceId = \DB::table('menu_item')->where('url',$slug)->where('site_key',$site_key)->first()->id;
        $cptUrlHandler->setInstanceId($instanceId);

        $instanceConfig = InstanceConfig::instance();
        $instanceConfig->setInstanceId($instanceId);
        $instanceConfig->setModule('module/cpt@cpt');
        $instanceConfig->setUrl(route('ahib::cpt',['instanceId' => $instanceId],false));
        $this->instanceId = $instanceConfig->getInstanceId();

        parent::__construct($configHandler,$cptUrlHandler,$dfDocHandler,$dynamicFactoryService,$identifyManager);

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/cpt');
//        XePresenter::share('instanceId',$instanceId);
    }

    public function store(
        CptDocService $service,
        Request $request,
        Validator $validator,
        CptPermissionHandler $cptPermission
    ) {
        parent::store($service,$request,$validator,$cptPermission);

        return redirect()->to(route('ah::closer',$request->all()));
    }

    public function update(
        CptDocService $service,
        Request $request,
        Validator $validator,
        IdentifyManager $identifyManager,
        $menuUrl
    ) {
        parent::update($service,$request,$validator,$identifyManager,$menuUrl);

        return redirect()->to(route('ah::closer',$request->all()));
    }

    public function destroy(
        CptDocService $service,
        Request $request,
        Validator $validator,
        IdentifyManager $identifyManager,
        $menuUrl,
        $id
    ) {
        parent::destroy($service,$request,$validator,$identifyManager,$menuUrl,$id);
        return redirect()->to(route('ah::closer',$request->all()));
    }
}
