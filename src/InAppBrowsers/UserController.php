<?php

namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use App\Http\Controllers\User\UserController as XeUserController;
use XePresenter;
use XeTheme;

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
class UserController extends XeUserController
{
    /**
     * UserController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/user/settings');
    }

}
