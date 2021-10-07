<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\ApplicationHelper\Plugin as Plugin;
use App\Facades\XeFrontend;
use App\Facades\XePresenter;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Http\Request;
use Xpressengine\Menu\Models\Menu;
use Xpressengine\Routing\InstanceRoute;

class InAppController extends BaseController
{
    public function __construct()
    {
    }

    public function userRegister(){
        return view('test');
    }
}
