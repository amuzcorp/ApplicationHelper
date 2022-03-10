{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}
{{ XeFrontend::css('plugins/user_types/assets/fontawesome-free-5.15.1-web/css/all.min.css')->load() }}
{{ XeFrontend::css('plugins/user_types/assets/style.css')->load() }}
{{ XeFrontend::js('assets/core/user/user_register.js')->load() }}

<!-- 회원가입 폼  -->
<!-- [D] 회원가입 폼 영역은 가로 길이 때문에 class="user--signup" 추가 -->
<div class="user user--signup">
    <p class="user__text">가입 형태 선택</br>
        직업을 선택해 주세요.</p>


    <form id="group_form" action="{{ route('ahib::post_group_select') }}" method="post" data-rule="join" data-rule-alert-type="form">
        <input type="hidden" name="select_group_id" value="" />
        {{ csrf_field() }}
        <fieldset>
            {{--            <legend>{{ xe_trans('xe::signUp') }}</legend>--}}
            @foreach($groups as $group)
                <div class="meemong-card" data-group_id="{{ $group->id }}">
                    <div class="inner">
                        <div class="iconWrap" style="background-color: {{ $group->config->get('bg_color') ? $group->config->get('bg_color') : '#6b8eff' }}; color: {{ '#000000' }}">
                            <img src="{{ $group->config->get('description') }}" />
                            <h2>{{ $group->name }}</h2>
                        </div>
                    </div>
                </div>
            @endforeach
        </fieldset>
    </form>
</div>
<!-- //로그인 폼  -->
<script>
    $(document).ready(function() {
        $('.meemong-card').on('click', function() {
            var group_id = $(this).data('group_id');
            $('input[name=select_group_id]').val(group_id);

            $('#group_form').submit();
        });
    });
</script>
