<?php
namespace Amuz\XePlugin\ApplicationHelper\InAppBrowsers;

use Amuz\XePlugin\UserTypes\Contracts\User as UserContract;
use Amuz\XePlugin\UserTypes\Exceptions\ExistsAccountException;
use Amuz\XePlugin\UserTypes\Exceptions\ExistsEmailException;
use App\Http\Controllers\Auth\RegisterController as XeRegisterController;
use Amuz\XePlugin\UserTypes\Controllers\RegisterController as UserTypesRegisterController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use XePresenter;
use XeFrontend;
use XeTheme;
use XeConfig;
use XeDB;
use Xpressengine\Support\Exceptions\HttpXpressengineException;
use Xpressengine\User\Models\User;
use Xpressengine\User\Parts\AgreementPart;
use Xpressengine\User\Parts\DefaultPart;
use Xpressengine\User\Parts\RegisterFormPart;
use Xpressengine\User\UserInterface;
use Xpressengine\User\UserRegisterHandler;
use RuntimeException;

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
        if ($user_types === false || $user_types->getStatus() != 'activated') {
            parent::postRegister($request);
        } else {
            $this->userTypesPostRegister($request);
        }
        return redirect()->to(route('ah::closer',['isRegistered' => true, 'email' => $request->get('email'), 'password' => $request->get('password')]));
    }

    public function postGroupSelect(Request $request)
    {
        if(empty($request->get('select_group_id'))) {
            return redirect()->back()->with('alert', ['type' => 'error', 'message' => '가입 회원 유형을 선택 해 주세요.']);
        }else{
            $request->session()->put('select_group_id', $request->get('select_group_id'));
        }
//        return redirect()->route('ahib::user_register', $request->except('_token'));
        return $this->userTypesGetRegister($request);
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

        $select_group_id = $request->select_group_id;
        $groupConfig = app('amuz.usertype.config')->get($select_group_id);

        // 활성화된 가입폼 가져오기
        $parts = $this->getRegisterParts($request);
        $parts['amuz-default-info']->setGroupId($select_group_id);

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

        $userContract = $request->session()->get('userContract');
        if($userContract) {
            $isEmailDuplicated = app('xe.user')->users()->where('email', $userContract->getEmail())->exists();
        } else {
            $isEmailDuplicated = false;
        }
        $providerName = $request->session()->get('provider');

        $pluginHandler = app('xe.plugin');
        $user_types = $pluginHandler->getPlugin('user_types');
        $userTypesPlugin = false;
        if ($user_types && $user_types->getStatus() != 'activated') {
            $userTypesPlugin = true;
        }

        return \XePresenter::make('register.create',
            compact(
                'config', 'parts', 'groupConfig', 'select_group_id', 'userContract', 'providerName', 'isEmailDuplicated', 'userTypesPlugin'
            )
        );
    }

    public function userTypesPostRegister(Request $request)
    {
        // validation
        if (!$this->checkJoinable()) {
            return redirect()->back()->with(
                ['alert' => ['type' => 'danger', 'message' => xe_trans('xe::joinNotAllowed')]]
            );
        }

        $config = app('xe.config')->get('user.register');

        $socialLogin = $request->get('social_login') ?: 'N';

        // 활성화된 가입폼 가져오기

        $userData = $request->except(['_token']);

        // set join group
        $joinGroup = $config->get('joinGroup');
        if ($joinGroup !== null) {
            $userData['group_id'] = [$joinGroup];
        }

        // 그룹이 2개 이상일때는 선택 한 그룹으로
        if($request->get('select_group_id') != null) {
            $userData['group_id'] = [$request->get('select_group_id')];
        }

        if ($request->session()->has('user_agree_terms') === true) {
            $userData['user_agree_terms'] = $request->session()->pull('user_agree_terms');
        } elseif ($request->has('agree') === true) {
            $enableTermIds = [];
            $enableTerms = $this->termsHandler->fetchEnabled();
            foreach ($enableTerms as $term) {
                $enableTermIds[] = $term->id;
            }

            $userData['user_agree_terms'] = $enableTermIds;
        }

        $parts = $this->getRegisterParts($request);
        if($socialLogin === 'Y') {
            $parts->each(function (RegisterFormPart $part) use ($request) {
                if ($part::ID === DefaultPart::ID) {
                    $rule = $part->rules();
                    unset($rule['password']);

                    $this->validate($request, $rule);
                }
                elseif ($part::ID === AgreementPart::ID) {
                    $requireTerms = app('xe.terms')->fetchRequireEnabled();
                    $termAgreeType = app('xe.config')->getVal('user.register.term_agree_type');

                    // UserRegisterHandler::TERM_AGREE_PRE = 회원약관 동의가 회원정보 입력전에 출력 될 경우
                    if ($requireTerms->count() > 0 && $termAgreeType !== UserRegisterHandler::TERM_AGREE_PRE) {
                        $requireTermValidator = Validator::make(
                            $request->all(),
                            [],
                            ['user_agree_terms.accepted' => xe_trans('xe::pleaseAcceptRequireTerms')]
                        );

                        $requireTermValidator->sometimes(
                            'user_agree_terms',
                            'accepted',
                            function ($input) use ($requireTerms) {
                                $userAgreeTerms = $input['user_agree_terms'] ?? [];

                                foreach ($requireTerms as $requireTerm) {
                                    if (in_array($requireTerm->id, $userAgreeTerms) === false) {
                                        return true;
                                    }
                                }

                                return false;
                            }
                        )->validate();
                    }
                }
                else {
                    $part->validate();
                }
            });

            XeDB::beginTransaction();
            try {
                $user = $this->registerUser($request->except(['_token']));
                $userData['id'] = $user->id;    // 생성된 user id
                app('amuz.usertype.handler')->insertDf($userData);  // 회원 그룹별 확장 필드 저장
                if(isset($request->profile_img_id) && $request->profile_img_id !== '' && $request->profile_img_id) {
                    \XeDB::table('user')->where('id', $user->id)->update(['profile_image_id' => $request->profile_img_id]);
                };
            } catch (ExistsAccountException $e) {
                XeDB::rollback();
                $this->throwHttpException(xe_trans('user_types::alreadyRegisteredAccount'), 409, $e);
            } catch (ExistsEmailException $e) {
                XeDB::rollback();
                $this->throwHttpException(xe_trans('user_types::alreadyRegisteredEmail'), 409, $e);
            } catch (\Throwable $e) {
                XeDB::rollback();
                throw $e;
            }
            XeDB::commit();

        } else {
            $parts->each(function ($part) {
                $part->validate();
            });

            XeDB::beginTransaction();
            try {
                $user = $this->handler->create($userData);
                $userData['id'] = $user->id;    // 생성된 user id
                app('amuz.usertype.handler')->insertDf($userData);  // 회원 그룹별 확장 필드 저장
                if(isset($request->profile_img_id) && $request->profile_img_id !== '' && $request->profile_img_id) {
                    \XeDB::table('user')->where('id', $user->id)->update(['profile_image_id' => $request->profile_img_id]);
                };
            } catch (\Exception $e) {
                XeDB::rollback();
                throw $e;
            }
            XeDB::commit();

        }

        //이메일 인증 후 가입 옵션을 사용 했을 때 회원가입 후 인증 메일 발송
        if ($user->status === User::STATUS_PENDING_EMAIL) {
            $this->sendApproveEmail($user);
        }

        // login
        if (app('config')->get('xe.user.registrationAutoLogin') === true) {
            $this->auth->login($user);

            switch ($user->status) {
                case User::STATUS_PENDING_ADMIN:
                    return redirect()->route('auth.pending_admin');
                    break;

                case User::STATUS_PENDING_EMAIL:
                    return redirect()->route('auth.pending_email');
                    break;
            }
        }

        // login
        return true;
    }

    protected function getRegisterParts(Request $request)
    {
        $config = app('xe.config')->get('user.register');

        $parts = $this->handler->getRegisterParts();
        $activated = array_keys(array_intersect_key(array_flip($config->get('forms', [])), $parts));

        $parts = collect($parts)->filter(function ($part, $key) use ($activated) {
            return in_array($key, $activated) || $part::isImplicit();
        })->sortBy(function ($part, $key) use ($activated) {
            return array_search($key, $activated);
        })->map(function ($part) use ($request) {
            return new $part($request);
        });

        return $parts;
    }

    /**
     * 회원가입시 회원정보 입력 전에 약관 동의를 진행 할 경우 validation 처리
     *
     * @param Request $request request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postTermAgree(Request $request)
    {
        $terms = $this->termsHandler->fetchRequireEnabled();
        // 선택된 그룹에 매칭된 약관 id 를 가져온다
        $group_id = $request->session()->get('select_group_id');
        $group_config = app('amuz.usertype.config')->get($group_id);
        if($group_config !== null) {
            $selected_terms = $group_config->get('selected_terms') ? $group_config->get('selected_terms') : [];

            // 선택 안된 약관은 삭제
            foreach ($terms as $key => $term) {
                if (!in_array($term->id, $selected_terms)) {
                    unset($terms[$key]);
                }
            }
        }

        $rule = [];
        foreach ($terms as $term) {
            $rule[$term->id] = 'bail|accepted';
        }

        $this->validate(
            $request,
            $rule,
            ['*.accepted' => xe_trans('xe::pleaseAcceptRequireTerms')]
        );

        $request->session()->flash('pass_agree');
        $request->session()->forget('select_group_id');
        $request = $request->merge(['select_group_id' => $group_id]);

        return redirect()->route('ahib::user_register', $request->except('_token'));
    }

    /**
     * find account
     *
     * @param string $userContractId user info
     * @param string $providerName   providerName
     *
     * @return \Xpressengine\User\Models\UserAccount|null
     */
    protected function findAccount($userContractId, $providerName)
    {
        if ($userContractId instanceof UserContract) {
            $userContractId = $userContractId->getId();
        }

        return app('xe.user')->accounts()
            ->where(['provider' => $providerName, 'account_id' => $userContractId])
            ->first();
    }

    /**
     * Register user
     *
     * @param array $userData Register user data
     *
     * @return UserInterface
     */
    public function registerUser($userData)
    {
        $user = app('xe.user');
        $cfg = app('xe.config');
        $config = app('xe.config')->get('user.register');

        $email = array_get($userData, 'email', null);
        $accountId = array_get($userData, 'account_id', null);
        $providerName = array_get($userData, 'provider_name', null);

        if ($user->users()->where('email', $email)->exists() === true) {
            throw new ExistsEmailException;
        }

        if ($this->findAccount($accountId, $providerName) !== null) {
            throw new ExistsAccountException;
        }

        $userAccountData = [
            'email' => array_get($userData, 'email', null),
            'account_id' => $accountId,
            'provider' => $providerName,
            'token' => array_get($userData, 'token', null),
            'token_secret' => array_get($userData, 'token_secret', null) ?? ''
        ];

        $loginId = array_get($userData, 'login_id', strtok($email, '@'));
        $userData['login_id'] = $this->resolveLoginId($loginId);
        $userData['display_name'] = array_get($userData, 'display_name', null);

        // set join group
        $joinGroup = $config->get('joinGroup');
        if ($joinGroup !== null) {
            $userData['group_id'] = [$joinGroup];
        }

        // 그룹이 2개 이상일때는 선택 한 그룹으로
        if($userData['select_group_id'] != null) {
            $userData['group_id'] = [$userData['select_group_id']];
        }

        //선택된 그룹에 1번 id가 없을 경우 추가
//        $groups = $this->handler->groups()->query()->where('site_key',\XeSite::getCurrentSiteKey())->get();
//        if(in_array($groups->first()->id, $userData['group_id']) === false) {
//            $userData['group_id'][] = $groups->first()->id;
//        }

        $userData['account'] = $userAccountData;

        if ($cfg->getVal('user.register.register_process') === User::STATUS_PENDING_EMAIL) {
            $userData['status'] = User::STATUS_ACTIVATED;
            if ($email !== array_get($userData, 'contract_email', null)) {
                $userData['status'] = User::STATUS_PENDING_EMAIL;
            }
        }
        return $user->create($userData);
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

    /**
     * Resolve loginId
     *
     * @param string $loginId loginId
     *
     * @return string
     */
    private function resolveLoginId($loginId)
    {
        $i = 1;

        $resolveLoginId = $loginId;
        while (true) {
            if (app('xe.user')->users()->where('login_id', $resolveLoginId)->exists() === false) {
                break;
            }
            $resolveLoginId .= $i;
        }

        return $resolveLoginId;
    }

    /**
     * 회원가입 인증 메일 발송
     *
     * @param UserInterface $user userItem
     *
     * @return void
     */
    protected function sendApproveEmail(UserInterface $user)
    {
        $tokenRepository = app('xe.user.register.tokens');

        $mail = $this->handler->createEmail($user, ['address' => $user->email], false);
        $token = $tokenRepository->create('register', ['email' => $user->email, 'user_id' => $user->id]);
        $this->emailBroker->sendEmailForRegisterApprove($mail, $token);
    }

    /**
     * throw http exception
     *
     * @param string $msg      massage
     * @param null   $code     code
     * @param null   $previous previous
     *
     * @return void
     * @throws HttpXpressengineException
     */
    protected function throwHttpException($msg, $code = null, $previous = null)
    {
        $e = new HttpXpressengineException([], $code, $previous);
        $e->setMessage($msg);

        throw $e;
    }
}
