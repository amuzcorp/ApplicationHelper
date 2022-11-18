{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}
{{ XeFrontend::js('plugins/adapfit/src/DynamicFieldSkins/TextMobileSkin/assets/jquery.mask.js')->load() }}

<div class="user user--signup-complete">
    @if(Session::pull('status') !== 'login_id.sent')
        <h2 class="user__title">아이디를 잊었습니까?</h2>
        <p class="user__text">회원가입시 등록한 개인정보를 입력해주세요.<br/>로그인 아이디, 이메일 주소를 찾을 수 있습니다.</p>

        <form role="form" method="post" action="{{ route('ahib::find_login.post') }}">
            {{  csrf_field() }}
            <div class="xu-form-group">
                <div class="xu-form-group__box">
                    <label class="xu-form-group__label">이름</label>
                    <input type="text" name="display_name" class="xu-form-group__control" placeholder="이름을 입력해주세요">
                </div>
            </div>
            <div class="xu-form-group">
                <div class="xu-form-group__box">
                    <label class="xu-form-group__label">연락처</label>
                    <input type="text" id="mobile" name="mobile" class="xu-form-group__control" placeholder="000-0000-0000" required minlength="13" maxlength="13">
                </div>
            </div>
            <div class="xu-form-group">
                <div class="xu-form-group__box">
                    <label class="xu-form-group__label">생년월일</label>
                    <input type="text" name="birth_bate" class="xu-form-group__control" placeholder="(ex. 19801231)" required minlength="8" maxlength="8">
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="xu-button xu-button--primary">
                    <span class="xu-button__text">{{ xe_trans('xe::next') }}</span>
                </button>
                <a href="{{route('ahib::pass_reset')}}" class="xu-button xu-button--default">
                    <span class="xu-button__text">비밀번호 찾기</span>
                </a>
                {{--                <a href="{{ route('login') }}" class="xu-button xu-button--link">{{ xe_trans('xe::login') }}</a>--}}
            </div>
        </form>

        <script>
            $(document).ready(function() {
                $('#mobile').mask('000-0000-0000');
            });
        </script>
    @else
        <style>
            .find-login-id-div {
                border: 1px solid #939393;
            }
            .find-login-id-body {
                padding-top:20px;
                padding-bottom:20px;
            }
            .find-login-id {
                font-weight: bold;
                font-size: 17px;
                margin-bottom: 5px;
            }
            .find-email {
                font-size: 15px;
                color: #a1a1a1;
            }
            .text-center {
                text-align: center;
            }
            .mt-1-5 {
                margin-top: 1.5rem;
            }
        </style>
        <!-- 비밀번호 찾기 2step-->
        <div class="user find-password text-center">
            <h2 class="user__title">계정정보를 확인해주세요</h2>
            @php
                $result = Session::pull('result');
            @endphp
            {{--            <p class="user__text">{!! xe_trans('xe::checkFindPasswordEmailDescription', ['email' => sprintf('<em>%s</em>', $email)]) !!}</p>--}}
            <em class="info-title mb-4">총 <span style="color:#00a9d4">{{count($result)}}</span>개</em>
            @foreach($result as $user)
                <div class="xu-form-group mt-1-5">
                    <div class="find-login-id-div">
                        <div class="find-login-id-body text-center">
                            <div class="find-login-id">{{$user->login_id}}</div>
                            <div class="find-email">{{$user->email}}</div>
                        </div>
                    </div>
                </div>
            @endforeach
            <a href="{{route('ahib::pass_reset')}}" class="xu-button xu-button--link">
                <span class="xu-button__text">비밀번호 찾기</span>
            </a>
            {{--        <a href="{{ route('login') }}" class="xu-button xu-button--link">{{ xe_trans('xe::login') }}</a>--}}
        </div>
        <!-- // 비밀번호 찾기 2step-->
    @endif
</div>
