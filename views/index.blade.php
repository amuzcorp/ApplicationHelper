<div class="title">{{ $title }}</div>

<dl>
    <dt>GET</dt>
    <dl>{{route('ah::get_token')}}</dl>

    <dt>POST</dt>
    <dl>{{route('ah::post_login')}}</dl>
    <dl>{{route('ah::token_login')}}</dl>
</dl>
