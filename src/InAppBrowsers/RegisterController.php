<?php
namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use App\Http\Controllers\Auth\RegisterController as XeRegisterController;
use Amuz\XePlugin\UserTypes\Controllers\RegisterController as UserTypesRegisterController;
use Illuminate\Http\Request;
use XePresenter;
use XeFrontend;
use XeTheme;
use XeConfig;
use Xpressengine\User\UserRegisterHandler;

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
        $this->auth->logout();

        XeTheme::selectBlankTheme();
        XePresenter::setSkinTargetId('ahib/user/auth');
    }
//
    public function getRegister(Request $request,$group_id = null)
    {
        $pluginHandler = app('xe.plugin');
        $user_types = $pluginHandler->getPlugin('user_types');
        if (!$user_types || $user_types->getStatus() != 'activated') {
            return parent::getRegister($request); // TODO: Change the autogenerated stub
        } else{
            return $this->userTypesGetRegister($request, $group_id);
        }
    }

    public function postRegister(Request $request){
        $pluginHandler = app('xe.plugin');
        $user_types = $pluginHandler->getPlugin('user_types');
        if (!$user_types || $user_types->getStatus() != 'activated') {
            parent::postRegister($request);
        } else {

        }
        return redirect()->to(route('ah::closer',$request->all()));
    }

    public function postGroupSelect(Request $request)
    {
        if(empty($request->get('select_group_id'))) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => '가입 회원 유형을 선택 해 주세요.']);
        }else{
            $request->session()->put('select_group_id', $request->get('select_group_id'));
        }
        return redirect()->route('ahib::user_register', $request->except('_token'));
//        return $this->userTypesGetRegister($request);
    }

    /**
     * Show the application registration form.
     *
     * @param Request $request request
     * @return \Xpressengine\Presenter\Presentable
     */
    public function userTypesGetRegister(Request $request, $group_id = null)
    {
        // 회원 가입 허용 검사
        if (!$this->checkJoinable()) {
            return redirect()->back()->with(
                ['alert' => ['type' => 'danger', 'message' => xe_trans('xe::joinNotAllowed')]]
            );
        }

        $request->session()->forget('user_agree_terms');
        $groups = app('amuz.usertype.handler')->getEnabledGroups();

        // 1. 선택된 그룹이 2개 이상인지.
        if(count($groups) > 1) {
            // 2. 그룹 ID 선택 안되어있으면 그룹선택 화면으로
            if (!isset($request->select_group_id)) {
                return \XePresenter::make('register.group', compact('groups'));
            }
        }

        //약관을 회원정보 입력 전에 받는 경우 처리
        $agreeType = app('xe.config')->getVal(
            'user.register.term_agree_type',
            UserRegisterHandler::TERM_AGREE_WITH
        );
        $terms = $this->termsHandler->fetchEnabled();

        $isAllRequireTermAgree = true;
        $requireTerms = $this->termsHandler->fetchRequireEnabled();

        if(count($groups) > 1) {
            // 선택된 그룹에 매칭된 약관 id 를 가져온다
            $group_id = $request->select_group_id;
            $group_config = app('amuz.usertype.config')->get($group_id);
            $selected_terms = $group_config->get('selected_terms') ? $group_config->get('selected_terms') : [];

            // 선택 안된 약관은 삭제
            foreach ($terms as $key => $term) {
                if (!in_array($term->id, $selected_terms)) {
                    unset($terms[$key]);
                }
            }
            // 선택 안된 필수 약관은 삭제
            foreach ($requireTerms as $key => $requireTerm) {
                if (!in_array($requireTerm->id, $selected_terms)) {
                    unset($requireTerms[$key]);
                }
            }
        }

        foreach ($requireTerms as $requireTerm) {
            if ($request->has($requireTerm->id) === false) {
                $isAllRequireTermAgree = false;
                break;
            }
        }

        if ($agreeType === UserRegisterHandler::TERM_AGREE_PRE && count($terms) > 0 &&  //가입 정보 이전에 약관 동의 출력 여부
            (
                $isAllRequireTermAgree === false || //필수 조건이 있는데 선택 안했을 경우
                ($requireTerms->count() === 0 && $request->session()->has('pass_agree') === false))
            //약관에 선택 약관만 존재하는데 session에 약관 동의에 대한 데이터가 없을 경우
        ) {
            return \XePresenter::make('register.agreement', compact('terms'));
        }

        return $this->userTypesGetRegisterForm($request);
    }

    /**
     * Show the application registration form.
     *
     * @param Request $request request
     * @return \Xpressengine\Presenter\Presentable
     */
    protected function userTypesGetRegisterForm(Request $request)
    {
        $config = app('xe.config')->get('user.register');

        $userHandler = app('xe.user');

        // 활성화된 가입폼 가져오기
        $parts = $userHandler->getRegisterParts();
        $activated = array_keys(array_intersect_key(array_flip($config->get('forms', [])), $parts));

        $parts = collect($parts)->filter(function ($part, $key) use ($activated) {
            return in_array($key, $activated) || $part::isImplicit();
        })->sortBy(function ($part, $key) use ($activated) {
            return array_search($key, $activated);
        })->map(function ($part) use ($request) {
            return new $part($request);
        });

        $rules = $parts->map(function ($part) {
            return $part->rules();
        })->collapse()->all();

        XeFrontend::rule('join', $rules);

        $userAgreeTerms = [];
        $enableTerms = $this->termsHandler->fetchEnabled();
        foreach ($enableTerms as $enableTerm) {
            if ($request->has($enableTerm->id) === true) {
                $userAgreeTerms[] = $enableTerm->id;
            }
        }

        if (count($userAgreeTerms) > 0) {
            $request->session()->put('user_agree_terms', $userAgreeTerms);
        }

        expose_trans('xe::passwordIncludeNumber');
        expose_trans('xe::passwordIncludeCharacter');
        expose_trans('xe::passwordIncludeSpecialCharacter');

        $pluginHandler = app('xe.plugin');
        $userTypes = $pluginHandler->getPlugin('user_types');

        $userGroup = $request->select_group_id;

        return \XePresenter::make('register.create', compact('config', 'parts', 'userTypes', 'userGroup'));
    }


    /**
     * Indicate able to join
     *
     * @return boolean
     */
    protected function checkJoinable()
    {
        return XeConfig::getVal('user.register.joinable') === true;
    }

}
