<?php
namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use App\Http\Controllers\Auth\RegisterController as XeRegisterController;
use Illuminate\Http\Request;
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
     * redirect path
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * RegisterController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/user/auth');
    }


    public function postRegister(Request $request){
        parent::postRegister($request);
        return redirect()->to(route('ah::closer',$request->all()));
    }
}