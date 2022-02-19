{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}
{{ XeFrontend::css('plugins/user_types/assets/fontawesome-free-5.15.1-web/css/all.min.css')->load() }}
{{ XeFrontend::css('plugins/user_types/assets/style.css')->load() }}
{{ XeFrontend::js('assets/core/user/user_register.js')->load() }}

<!-- 회원가입 폼  -->
<!-- [D] 회원가입 폼 영역은 가로 길이 때문에 class="user--signup" 추가 -->
<div class="user user--signup">
    <h2 class="user__title">회원 유형 선택</h2>
    <p class="user__text">가입 할 회원 유형을 선택해주세요.</p>

    <form id="group_form" action="{{ route('auth.register.group_select') }}" method="post" data-rule="join" data-rule-alert-type="form">
        <input type="hidden" name="select_group_id" value="" />
        {{ csrf_field() }}
        <fieldset>
            <legend>{{ xe_trans('xe::signUp') }}</legend>

            <div class="user-group">
                @foreach($groups as $group)
                    <div class="flip-card" data-group_id="{{ $group->id }}">
                        <div class="flip-card-inner">
                            <div class="flip-card-front" style="background-color: {{ $group->config->get('bg_color') ? $group->config->get('bg_color') : '#6b8eff' }}; color: {{ $group->config->get('fg_color') ? $group->config->get('fg_color') : '#ffffff' }}">
                                <i class="{{ $group->config->get('icp_class') }}"></i>
                                <h2>{{ $group->name }}</h2>
                            </div>
                            <div class="flip-card-back" style="background-color: {{ $group->config->get('bg_color') ? $group->config->get('bg_color') : '#6b8eff' }}; color: {{ $group->config->get('fg_color') ? $group->config->get('fg_color') : '#ffffff' }}">
                                <h2>{{ $group->name }}</h2>
                                <p>{{ $group->config->get('description') }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </fieldset>
    </form>
</div>
<!-- //로그인 폼  -->
<script>
    $(document).ready(function() {
        $('.flip-card').on('click', function() {
            var group_id = $(this).data('group_id');
            $('input[name=select_group_id]').val(group_id);

            $('#group_form').submit();
        });
    });
</script>
