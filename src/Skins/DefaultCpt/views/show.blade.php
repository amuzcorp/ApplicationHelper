<div class="board_read">
    <div class="read_header">
        <span class="category">카테고리</span>
        <h1><a href="#">{!! $item->title !!}</a></h1>

        <div class="more_info">
            @if ($item->hasAuthor())
                <span class="xe-dropdown">
                    <a href="{{ sprintf('/@%s', $item->getUserId()) }}" class="mb_autohr"
                       data-toggle="xe-page-toggle-menu"
                       data-url="{{ route('toggleMenuPage') }}"
                       data-data='{!! json_encode(['id'=>$item->getUserId(), 'type'=>'user']) !!}'>{{ $item->writer }}</a>
               </span>
            @else
                <span>
                    <a class="mb_autohr">{{ $item->writer }}</a>
                </span>
            @endif

            <span class="mb_time" title="{{$item->created_at}}"><i class="xi-time"></i> <span data-xe-timeago="{{$item->created_at}}">{{$item->created_at}}</span></span>
            <span class="mb_readnum"><i class="xi-eye"></i> {{$item->read_count}}</span>
        </div>
    </div>
    <div class="read_body">
        <div class="xe_content xe-content xe-content-{{ $item->instance_id }}">
            {!! compile($item->instance_id, $item->content, $item->format === Overcode\XePlugin\DynamicFactory\Models\CptDocument::FORMAT_HTML) !!}
        </div>
    </div>
    <br/>
    @foreach ($fieldTypes as $dynamicFieldConfig)
        @if (($fieldType = XeDynamicField::getByConfig($dynamicFieldConfig)) != null && $dynamicFieldConfig->get('use') == true)
            <div class="__xe_ __xe_section">
                {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
            </div>
        @endif
    @endforeach
    <div class="read_footer">
        <div class="bd_function">
            <div class="bd_function_l">
                <!-- [D] 클릭시 클래스 on 적용 및 bd_like_more 영역 diplay:block -->
                @if ($config->get('assent') == true)
                    <a href="#" data-url="{{ $cptUrlHandler->get('vote', ['option' => 'assent', 'id' => $item->id]) }}" class="bd_ico bd_like @if($documentHandler->hasVote($item, Auth::user(), 'assent') === true) voted @endif"><i class="xi-thumbs-up"></i><span class="xe-sr-only">{{ trans('board::like') }}</span></a>
                    <a href="#" data-url="{{ $cptUrlHandler->get('votedUsers', ['option' => 'assent', 'id' => $item->id]) }}" class="bd_like_num" data-id="{{$item->id}}">{{$item->assent_count}}</a>
                @endif

                @if ($config->get('dissent') == true)
                    <a href="#" data-url="{{ $cptUrlHandler->get('vote', ['option' => 'dissent', 'id' => $item->id]) }}" class="bd_ico bd_like @if($documentHandler->hasVote($item, Auth::user(), 'dissent') === true) voted @endif"><i class="xi-thumbs-down"></i><span class="xe-sr-only">{{ trans('board::hate') }}</span></a>
                    <a href="#" data-url="{{ $cptUrlHandler->get('votedUsers', ['option' => 'dissent', 'id' => $item->id]) }}" class="bd_like_num bd_hate_num" data-id="{{$item->id}}">{{$item->dissent_count}}</a>
                @endif

                @if (Auth::check() === true)
                    <a href="#" data-url="{{$cptUrlHandler->get('favorite', ['id' => $item->id])}}" class="bd_ico bd_favorite @if($item->favorite !== null) on @endif __xe-bd-favorite"><i class="xi-star"></i><span class="xe-sr-only">{{ trans('board::favorite') }}</span></a>
                @endif

                {!! uio('share', [
                    'item' => $item,
                    'url' => Request::url(),
                ]) !!}
            </div>
            <div class="bd_function_r">
                <a href="{{ $cptUrlHandler->get('index', array_merge(Request::all())) }}" class="bd_ico bd_list"><i class="xi-list"></i><span class="xe-sr-only">리스트</span></a>
                @if($isManager == true || $item->user_id == Auth::user()->getId() || $item->user_type === $item::USER_TYPE_GUEST)
                    <a href="{{ $cptUrlHandler->get('edit', array_merge(Request::all(), ['id' => $item->id])) }}" class="bd_ico bd_modify"><i class="xi-eraser"></i><span class="xe-sr-only">{{ xe_trans('xe::update') }}</span></a>
                    <a href="#" class="bd_ico bd_delete" data-url="{{ $cptUrlHandler->get('destroy', array_merge(Request::all(), ['id' => $item->id])) }}"><i class="xi-trash"></i><span class="xe-sr-only">{{ xe_trans('xe::delete') }}</span></a>
                @endif
            </div>
        </div>
    </div>
</div>


@if($cpt->use_comment == "Y")
    <div class="__xe_comment board_comment">
        {!! uio('comment', ['target' => $item]) !!}
    </div>
@endif
