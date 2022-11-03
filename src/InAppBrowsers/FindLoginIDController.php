<?php
/**
 * PasswordController.php
 *
 * PHP version 7
 *
 * @category    Controllers
 * @package     App\Http\Controllers\Auth
 * @license     https://opensource.org/licenses/MIT MIT
 * @link        https://laravel.com
 */
namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use XePresenter;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use XeTheme;
use Xpressengine\User\UserHandler;

class FindLoginIDController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * @var UserHandler
     */
    protected $handler;

    /**
     * Create a new password controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\Guard          $auth      Guard instance
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
        $this->handler = app('xe.user');
        app('auth')->logout();

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/user/auth');

        $this->middleware('guest');
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Xpressengine\Presenter\Presentable
     */
    public function getFindLoginID(Request $request)
    {
        if(app('auth')->check()) {
            app('auth')->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return XePresenter::make('find_login_id');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param Request $request request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postFindLoginID(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required',
            'mobile' => 'required',
            'birth_bate' => 'required'
        ]);

        $display_name = $request->get('display_name');
        $mobile = $request->get('mobile');
        $birth_bate = $request->get('birth_bate');

        $user = \XeUser::where('display_name', $display_name)->get();
        if(!$user->first()) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '입력한 정보와 일치하는 회원이 없습니다.']);
        } else {
            $result = $user->where('mobile_text', $mobile)->where('birthday_num', $birth_bate);
        }

        if(count($result) == 0) {
            return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '입력한 정보와 일치하는 회원이 없습니다.']);
        }

        return redirect()->back()->with('status', 'login_id.sent')->with('result', $result);
    }
}
