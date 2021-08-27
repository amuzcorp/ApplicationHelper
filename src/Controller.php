<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\Plugin\ApplicationHelper\Models\AhUserToken;
use Amuz\XePlugin\ApplicationHelper\BaseObject;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use XeFrontend;
use XePresenter;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\User\EmailBroker;
use Xpressengine\User\Guard;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserHandler;

class Controller extends BaseController
{

    use AuthenticatesUsers;

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
     * @var EmailBroker
     */
    protected $emailBroker;

    protected $authController;

    public function __construct()
    {
        $this->auth = app('auth');
        $this->handler = app('xe.user');
        $this->emailBroker = app('xe.auth.email');
        $this->authController = new AuthController();
    }

    public function index()
    {
        $title = '애플리케이션 API 헬퍼';

        // set browser title
        XeFrontend::title($title);

        // load css file
        XeFrontend::css(Plugin::asset('assets/style.css'))->load();

        // output
        return view('ApplicationHelper::views.index', ['title' => $title]);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postLogin(Request $request)
    {
        $retObj = new BaseObject();

        $deviceInfo = [
            'device_name' => $request->header('X_AMUZ_DEVICE_NAME'),
            'device_version' => $request->header('X_AMUZ_DEVICE_VERSION'),
            'device_id' => $request->header('X_AMUZ_DEVICE_UUID'),
        ];
        foreach($deviceInfo as $val){
            if($val == null){
                $retObj->addError('ERR_NONE_ALLOW','허용되지 않은 접근입니다.');
                return $retObj->output();
            }
        }

        $this->authController->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);

        $this->checkCaptcha();

        $credentials = $request->only('email', 'password');

        $credentials['email'] = trim($credentials['email']);

        $credentials['status'] = [User::STATUS_ACTIVATED, User::STATUS_PENDING_ADMIN, User::STATUS_PENDING_EMAIL];


        if ($this->auth->attempt($credentials, true)) {
            $user = $this->auth->user();

            switch ($user->status) {
                case User::STATUS_PENDING_ADMIN:
                    $retObj->addError('ERR_PENDING_ADMIN','관리자 승인 대기중입니다.');
                    break;

                case User::STATUS_PENDING_EMAIL:
                    $retObj->addError('ERR_PENDING_EMAIL','메일 인증이 완료되지 않았습니다.');
                    break;

                default:
                    $user_token = AhUserToken::where('device_id',$deviceInfo['device_id'])->first();
                    $deviceInfo['user_id'] = $user->id;
                    if($user_token == null) $user_token = new AhUserToken($deviceInfo);
                    $user_token->save();


                    $retObj->setMessage("로그인에 성공하였습니다.");
                    $retObj->set('user',$user);
                    $retObj->set('remember_token',$user_token->token);
                    break;
            }
        }else{
            $retObj->addError('ERR_AccountNotFoundOrDisabled',xe_trans('xe::msgAccountNotFoundOrDisabled'));
        }
        return $retObj->output();
    }


    /**
     * Check captcha
     *
     * @return void
     */
    protected function checkCaptcha()
    {
        $config = app('xe.config')->get('user.register');
        if ($config->get('useCaptcha', false) === true) {
            if (app('xe.captcha')->verify() !== true) {
                throw new HttpException(Response::HTTP_FORBIDDEN, xe_trans('xe::msgFailToPassCAPTCHA'));
            }
        }
    }
}
