{{ XeFrontend::js('plugins/board/assets/js/board.js')->appendTo('body')->load() }}
{{ XeFrontend::css('plugins/board/assets/css/board.css')->load() }}

<div class="board">
    @section('content')
        {!! isset($content) ? $content : '' !!}
    @show
</div>
