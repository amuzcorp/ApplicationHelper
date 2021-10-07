<?php
namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use App\Http\Controllers\Auth\RegisterController as XeRegisterController;
use XePresenter;
use XeTheme;

/**
 * Class RegisterController
 *
 * @category    Controllers
 * @package     App\Http\Controllers\Auth
 * @license     https://opensource.org/licenses/MIT MIT
 * @link        https://laravel.com
 */
class RegisterController extends XeRegisterController
{
    /**
     * RegisterController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/user/auth');
    }
}
