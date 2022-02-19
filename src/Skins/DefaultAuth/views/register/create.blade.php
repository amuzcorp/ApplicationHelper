{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}
{{ XeFrontend::js('assets/core/user/user_register.js')->load() }}
@extends('ApplicationHelper::src.Skins.commonLayout')
@section('content')
<!-- 회원가입 폼  -->
<!-- [D] 회원가입 폼 영역은 가로 길이 때문에 class="user--signup" 추가 -->
<div class="user user--signup">
    <h2 class="user__title">{{ xe_trans('xe::signUp') }}</h2>
    <p class="user__text">{!! nl2br($config->get('register_guide')) !!}</p>

    @if($userContract)
        <form action="{{ route('auth.register') }}" method="post" data-rule="join" data-rule-alert-type="form">
    @else
        <form action="{{ route('ahib::user_register.store') }}" method="post" data-rule="join" data-rule-alert-type="form">
    @endif
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
                        {{ $part->render() }}
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
