{{ XeFrontend::js('assets/core/xe-ui-component/js/xe-page.js')->appendTo('body')->load() }}
<div class="board_list">
    <table>
        <!-- [D] 모바일뷰에서 숨겨할 요소 클래스 xe-hidden-xs 추가 -->
        <thead class="xe-hidden-xs">
        <!-- LIST HEADER -->
        <tr>
            <th>#</th>
        @foreach($dfConfig['listColumns'] as $columnName)
            @if($columnName === 'title')
            <th scope="col" class="title column-th-title"><span>제목</span></th>
            @else
            <th scope="col" class="column-th-{{$columnName}}"><span>{{ xe_trans($column_labels[$columnName]) }}</span></th>
            @endif
        @endforeach
        </tr>
        <!-- /LIST HEADER -->
        </thead>
        <tbody>
        @if (count($paginate) == 0)
            <!-- NO ARTICLE -->
            <tr class="no_article">
                <!-- [D] 컬럼수에 따라 colspan 적용 -->
                <td colspan="{{ count($dfConfig['listColumns']) + 1 }}">
                    <img src="{{ asset('plugins/board/assets/img/img_pen.jpg') }}" alt="">
                    <p>{{ xe_trans('xe::noPost') }}</p>
                </td>
            </tr>
            <!-- / NO ARTICLE -->
        @endif

        <!-- LIST -->
        @foreach($paginate as $item)
            <tr>
                <td>{{ $item->seq }}</td>
            @foreach($dfConfig['listColumns'] as $columnName)
                @if ($columnName == 'title')
                    <td class="title column-{{$columnName}} xe-hidden-xs">
                        <a href="{{ $cptUrlHandler->getShow($item, Request::all()) }}" id="title_{{$item->id}}" class="title_text">{!! $item->title !!}</a>
                    </td>
                @elseif ($columnName == 'writer')
                    <td class="author xe-hidden-xs">
                        @if ($item->hasAuthor() && $config->get('anonymity') === false)
                            <a href="#"
                               data-toggle="xe-page-toggle-menu"
                               data-url="{{ route('toggleMenuPage') }}"
                               data-data='{!! json_encode(['id'=>$item->getUserId(), 'type'=>'user']) !!}'>{!! $item->writer !!}</a>
                        @else
                            <a>{!! $item->writer !!}</a>
                        @endif
                    </td>
                @elseif ($columnName == 'read_count')
                    <td class="read_num xe-hidden-xs">{{ $item->{$columnName} }}</td>
                @elseif (in_array($columnName, ['created_at', 'updated_at', 'deleted_at']))
                    <td class="time xe-hidden-xs column-{{$columnName}}" title="{{ $item->{$columnName} }}" @if($item->{$columnName}->getTimestamp() > strtotime('-1 month')) data-xe-timeago="{{ $item->{$columnName} }}" @endif >{{ $item->{$columnName}->toDateString() }}</td>
                @elseif (($fieldType = XeDynamicField::get('documents_'.$dfConfig->get('cpt_id'), $columnName)) != null)
                    <td class="xe-hidden-xs column-{{$columnName}}">{!! $fieldType->getSkin()->output($columnName, $item->getAttributes()) !!}</td>
                @else
                    <td class="xe-hidden-xs column-{{$columnName}}">{!! $item->{$columnName} !!}</td>
                @endif
            @endforeach
                {{--모바일 사이즈 게시물 list--}}
                <td class="xe-visible-xs title column-title">
                    <a href="{{ $cptUrlHandler->getShow($item, Request::all()) }}" id="title_{{$item->id}}" class="title_text">{!! $item->title !!}</a>
                </td>
            </tr>
        @endforeach
        <!-- /LIST -->
        </tbody>
    </table>
</div>

<div class="board_footer">
    <!-- PAGINATAION PC-->
    {!! $paginate->render('dynamic_factory::components.Skins.Cpt.Common.views.default-pagination') !!}
    <!-- /PAGINATION PC-->

    <!-- PAGINATAION Mobile -->
    {!! $paginate->render('dynamic_factory::components.Skins.Cpt.Common.views.simple-pagination') !!}
    <!-- /PAGINATION Mobile -->
</div>
<div class="bd_dimmed"></div>
