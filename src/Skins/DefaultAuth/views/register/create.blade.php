{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}
{{ XeFrontend::js('assets/core/user/user_register.js')->load() }}
@extends('ApplicationHelper::src.Skins.commonLayout')
@section('content')
<!-- 회원가입 폼  -->
<!-- [D] 회원가입 폼 영역은 가로 길이 때문에 class="user--signup" 추가 -->
<div class="user user--signup">
    <h2 class="user__title">{{ xe_trans('xe::signUp') }}</h2>
    <p class="user__text">{!! nl2br($config->get('register_guide')) !!}</p>

    <form action="{{ route('ahib::user_register.store') }}" method="post" data-rule="join" data-rule-alert-type="form">
        {{ csrf_field() }}
        <input type="hidden" name="select_group_id" value="{{ $select_group_id }}" />
        @if($userContract)
            <input type="hidden" name="social_login" value="Y">
            <input type="hidden" name="account_id" value="{{ Request::old('account_id', $userContract->getId()) }}">
            <input type="hidden" name="provider_name" value="{{ Request::old('provider_name', $providerName) }}">
            <input type="hidden" name="token" value="{{ Request::old('token', $userContract->token) }}">
            <input type="hidden" name="token_secret" value="{{ Request::old('token_secret', $userContract->tokenSecret ?? '') }}">
            <input type="hidden" name="contract_email" value="{{ Request::old('contract_email', $userContract->getEmail()) }}">
        @endif
        <fieldset>
            <legend>{{ xe_trans('xe::signUp') }}</legend>

            <div class="user-signup">
                @foreach ($parts as $fieldId => $part)
                    @if($userContract)
                        @switch($fieldId)
                            @case("default-info")
                            <div class="xu-form-group xu-form-group--large">
                                <label class="xu-form-group__label" for="f-email">{{ xe_trans('xe::email') }}</label>
                                <div class="xu-form-group__box">
                                    <input type="text" id="f-email" class="xe-form-control xu-form-group__control"
                                           placeholder="{{ xe_trans('xe::enterEmail') }}" name="email" value="{{ old('email', $userContract->getEmail()) }}"
                                           required data-valid-name="{{ xe_trans('xe::email') }}"
                                           @if ($userContract->getEmail() !== null && $isEmailDuplicated === false) readonly @endif>
                                </div>
                            </div>

                            <div class="xu-form-group xu-form-group--large">
                                <label class="xu-form-group__label" for="f-login_id">{{ xe_trans('xe::id') }}</label>
                                <div class="xu-form-group__box">
                                    <input type="text" id="f-login_id" class="xe-form-control xu-form-group__control"
                                           placeholder="{{ xe_trans('xe::enterId') }}" name="login_id" value="{{ old('login_id', strtok($userContract->getEmail(), '@')) }}"
                                           required data-valid-name="{{ xe_trans('xe::id') }}">
                                </div>
                            </div>

                            @if (app('xe.config')->getVal('user.register.use_display_name') === true)
                                <div class="xu-form-group xu-form-group--large">
                                    <label class="xu-form-group__label" for="f-name">{{ xe_trans(app('xe.config')->getVal('user.register.display_name_caption')) }}</label>
                                    <div class="xu-form-group__box">
                                        <input type="text" id="f-name" class="xu-form-group__control"
                                               placeholder="{{ xe_trans('xe::enterDisplayName', ['displayNameCaption' => xe_trans(app('xe.config')->getVal('user.register.display_name_caption'))]) }}"
                                               name="display_name" value="{{ old('display_name', $userContract->getNickname() ?: $userContract->getName()) }}"
                                               required data-valid-name="{{ xe_trans(app('xe.config')->getVal('user.register.display_name_caption')) }}">
                                    </div>
                                </div>
                            @endif
                            @break
                            @default
                            {{ $part->render() }}
                            @break
                        @endswitch
                    @else
                        @if($fieldId == "default-info")

                            @inject('passwordValidator', 'xe.password.validator')
                            {{-- email --}}
                            <div class="xu-form-group xu-form-group--large">
                                <label class="xu-form-group__label" for="f-email">{{ xe_trans('xe::email') }}</label>
                                <div class="xu-form-group__box">
                                    <input type="text" id="f-email" class="xe-form-control xu-form-group__control" placeholder="ep-account@lge.com" name="email" value="{{ old('email') }}" required data-valid-name="이메일">
                                    <p style="padding:0; margin:0; color:red;">반드시 LG전자 EP계정을 입력해야 합니다.</p>
                                </div>
                            </div>

                            {{-- name --}}
                            <div class="xu-form-group xu-form-group--large">
                                <label class="xu-form-group__label" for="f-name">{{ xe_trans('xe::name') }}</label>
                                <div class="xu-form-group__box">
                                    <input type="text" id="f-name" class="xu-form-group__control" placeholder="{{ xe_trans('xe::enterName') }}" name="display_name" value="{{ old('display_name') }}" required data-valid-name="{{ xe_trans('xe::name') }}">
                                </div>
                            </div>

                            {{-- password --}}
                            <div class="xu-form-group xu-form-group--large">
                                <label class="xu-form-group__label" for="f-password">{{ xe_trans('xe::password') }}</label>
                                <div class="xu-form-group__box xu-form-group__box--icon-right">
                                    <input type="password" id="f-password" class="xu-form-group__control" placeholder="{{ xe_trans('xe::enterPassword') }}" name="password" required data-valid-name="{{xe_trans('xe::password')}}">
                                    <button type="button" class="xu-form-group__icon __xe-toggle-password">
                                        <i class="xi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            {{ $part->render() }}
                        @endif
                    @endif
                @endforeach
            </div>

            <button type="submit" class="xu-button xu-button--primary xu-button--block xu-button--large user-signup__button-signup">
                <span class="xu-button__text">{{ xe_trans('xe::signUp') }}</span>
            </button>
        </fieldset>
    </form>
</div>
<!-- //로그인 폼  -->
@endsection


<script>
    $(function () {
        $('.__xe-toggle-password').on('click', function () {
            var $self = $(this)
            var $prev = $self.prev()
            if ($prev.attr('type') === 'password') {
                $prev.attr('type', 'text')
                $self.find('i').addClass('xi-eye-off').removeClass('xi-eye')
            } else {
                $prev.attr('type', 'password')
                $self.find('i').addClass('xi-eye').removeClass('xi-eye-off')
            }
        })
    })
</script>
