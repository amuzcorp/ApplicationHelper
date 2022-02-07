<?php

namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use Route;
use XePresenter;
use XeTheme;
use Xpressengine\Http\Request;
use Xpressengine\Plugins\Board\BoardPermissionHandler;
use Xpressengine\Plugins\Board\ConfigHandler;
use Xpressengine\Plugins\Board\Controllers\BoardModuleController;
use Xpressengine\Plugins\Board\Handler;
use Xpressengine\Plugins\Board\IdentifyManager;
use Xpressengine\Plugins\Board\Services\BoardService;
use Xpressengine\Plugins\Board\UrlHandler;
use Xpressengine\Plugins\Board\Validator;
use Xpressengine\Routing\InstanceConfig;

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
class BoardController extends BoardModuleController
{
    /**
     * constructor.
     *
     * @param Handler $handler board handler
     * @param ConfigHandler $configHandler board config handler
     * @param UrlHandler $urlHandler board url handler
     */
    public function __construct(
        Handler       $handler,
        ConfigHandler $configHandler,
        UrlHandler    $urlHandler
    )
    {
        $instanceId = Route::current()->parameter('instance_id');
        $urlHandler->setInstanceId($instanceId);

        $instanceConfig = InstanceConfig::instance();
        $instanceConfig->setInstanceId($instanceId);
        $instanceConfig->setModule('module/board@board');
        $instanceConfig->setUrl(route('ahib::board',['instanceId' => $instanceId],false));
        $this->instanceId = $instanceConfig->getInstanceId();

        parent::__construct($handler,$configHandler,$urlHandler);

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/board');
//        XePresenter::share('instanceId',$instanceId);
    }

    public function store(
        BoardService $service,
        Request $request,
        Validator $validator,
        BoardPermissionHandler $boardPermission,
        IdentifyManager $identifyManager
    ) {
        parent::store($service,$request,$validator,$boardPermission,$identifyManager);

        return redirect()->to(route('ah::closer',$request->all()));
    }

    public function update(
        BoardService $service,
        Request $request,
        Validator $validator,
        IdentifyManager $identifyManager,
        $menuUrl
    ) {
        parent::update($service,$request,$validator,$identifyManager,$menuUrl);

        return redirect()->to(route('ah::closer',$request->all()));
    }

    public function destroy(
        BoardService $service,
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
