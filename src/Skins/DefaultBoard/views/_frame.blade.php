{{ XeFrontend::js('plugins/board/assets/js/board.js')->appendTo('body')->load() }}
{{ XeFrontend::css('plugins/board/assets/css/board.css')->load() }}
{{ XeFrontend::css('plugins/ApplicationHelper/src/Skins/DefaultBoard/assets/css/skin.css')->load() }}

<!-- BOARD -->
<div class="board">
    <!--topCommonContent-->
    @if ($config->get('topCommonContentOnlyList') === false || request()->segment(2) == '')
        {!! xe_trans($config->get('topCommonContent', '')) !!}
    @endif

    @section('content')
        {!! isset($content) ? $content : '' !!}
    @show

    <!--bottomCommonContent-->
    @if ($config->get('bottomCommonContentOnlyList') === false || request()->segment(2) == '')
            {!! xe_trans($config->get('bottomCommonContent', '')) !!}
    @endif

</div>
<!-- /BOARD -->
