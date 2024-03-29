<meta name="viewport" content="initial-scale=1.0, width=device-width">

@if(app('request')->isMobile())
    <style>
        .paymentButton {
            position: fixed;
            bottom: 0;
            width: 100%;
            left: 0;
            border: none;
            background: #FF9933;
            color: #ffffff;
            padding: 17px;
            font-size: 26px;
            font-weight: 500;
        }
        .pad-top-20 {
            padding-top:20px !important;
        }
        #xeContentEditor {
            min-height:300px !important;
        }
        .write_form_option {
            margin-bottom: 60px !important;
        }
        .write_footer {
            padding-bottom: 16px !important;
        }
        .write_footer .write_form_btn .btn_submit {
            background-color: #FF9933;
            border-color: #FF9933;
        }
        .write_footer .write_form_btn .btn_submit:focus {
            background-color: #e38527;
            border-color: #e38527;
        }
        .write_footer .write_form_btn .btn_submit:hover {
            background-color: #e38527;
            border-color: #e38527;
        }
        .xe-label>input[type="checkbox"]:hover+.xe-input-helper, .xe-label>input[type="radio"]:hover+.xe-input-helper {
            border-color: #FF9933;
        }
        .xe-label>input[type="checkbox"]:checked+.xe-input-helper {
            background-color: #FF9933;
        }
    </style>
@endif

{{ XeFrontend::rule('board', $rules) }}

{{ XeFrontend::js('assets/core/common/js/draft.js')->appendTo('head')->load() }}
{{ XeFrontend::css('assets/core/common/css/draft.css')->load() }}

@if($config->get('useTag') === true)
{{ XeFrontend::js('plugins/board/assets/js/BoardTags.js')->appendTo('body')->load() }}
@endif

<div class="board_write">
    <form method="post" id="board_form" class="__board_form" action="{{ route('ahib::board_update',['instance_id'=>$instanceId]) }}" enctype="multipart/form-data" data-rule="board" data-rule-alert-type="toast" data-instance_id="{{$item->instance_id}}" data-url-preview="{{ $urlHandler->get('preview') }}">
        <input type="hidden" name="_token" value="{{{ Session::token() }}}" />
        <input type="hidden" name="id" value="{{$item->id}}" />
        <input type="hidden" name="queryString" value="{{ http_build_query(Request::except('parent_id')) }}" />
        @foreach ($skinConfig['formColumns'] as $columnName)
        @if($columnName === 'title')
        <div class="write_header">
            <div class="write_category">
                @if($config->get('category') == true)
                {!! uio('uiobject/board@select', [
                'name' => 'category_item_id',
                'label' => xe_trans('xe::category'),
                'value' => $item->boardCategory != null ? $item->boardCategory->item_id : '',
                'items' => $categories,
                ]) !!}
                @endif
            </div>
            <div class="write_title">
                {!! uio('titleWithSlug', [
                'title' => Request::old('title', $item->title),
                'slug' => $item->getSlug(),
                'titleClassName' => 'bd_input',
                'config' => $config
                ]) !!}
            </div>
        </div>
        @elseif($columnName === 'content')
        <div class="write_body">
            <div class="write_form_editor">
                {!! editor($config->get('boardId'), [
                'content' => Request::old('content', $item->content),
                'cover' => true,
                ], $item->id, $thumb ? $thumb->board_thumbnail_file_id : null ) !!}
            </div>
        </div>

        @if($config->get('useTag') === true)
        {!! uio('uiobject/board@tag', [
        'tags' => $item->tags->toArray()
        ]) !!}
        @endif
        @else
        @if(isset($dynamicFieldsById[$columnName]) && $dynamicFieldsById[$columnName]->get('use') == true)
        <div class="__xe_{{$columnName}} __xe_section">
            {!! df_edit($config->get('documentGroup'), $columnName, $item->getAttributes()) !!}
        </div>
        @endif
        @endif
        @endforeach

        <div class="dynamic-field">
            @foreach ($fieldTypes as $dynamicFieldConfig)
            @if (in_array($dynamicFieldConfig->get('id'), $skinConfig['formColumns']) === false && ($fieldType = XeDynamicField::getByConfig($dynamicFieldConfig)) != null && $dynamicFieldConfig->get('use') == true)
            <div class="__xe_{{$dynamicFieldConfig->get('id')}} __xe_section">
                {!! $fieldType->getSkin()->edit($item->getAttributes()) !!}
            </div>
            @endif
            @endforeach
        </div>

        <div class="draft_container"></div>

        <div class="write_footer">
            <div class="write_form_input">
                @if ($item->user_type == $item::USER_TYPE_GUEST)
                <div class="xe-form-inline">
                    <input type="text" name="writer" class="xe-form-control" placeholder="{{ xe_trans('xe::writer') }}" title="{{ xe_trans('xe::writer') }}" value="{{ Request::old('writer', $item->writer) }}">
                    <input type="password" name="certify_key" class="xe-form-control" placeholder="{{ xe_trans('xe::password') }}" title="{{ xe_trans('xe::password') }}">
                    <input type="email" name="email" class="xe-form-control" placeholder="{{ xe_trans('xe::email') }}" title="{{ xe_trans('xe::email') }}" value="{{ Request::old('email', $item->email) }}">
                </div>
                @endif
            </div>
            <div class="write_form_option">
                <div class="xe-form-inline">
                    @if($config->get('comment') === true)
                    <label class="xe-label">
                        <input type="checkbox" name="allow_comment" value="1" @if($item->boardData->allow_comment == 1) checked="checked" @endif>
                        <span class="xe-input-helper"></span>
                        <span class="xe-label-text">{{xe_trans('board::allowComment')}}</span>
                    </label>
                    @endif

                    @if (Auth::check() === true)
                    <label class="xe-label">
                        <input type="checkbox" name="use_alarm" value="1" @if($item->boardData->use_alarm == 1) checked="checked" @endif>
                        <span class="xe-input-helper"></span>
                        <span class="xe-label-text">{{xe_trans('board::useAlarm')}}</span>
                    </label>
                    @endif

                    @if($config->get('secretPost') === true)
                    <label class="xe-label">
                        <input type="checkbox" name="display" value="{{$item::DISPLAY_SECRET}}" @if($item->display == $item::DISPLAY_SECRET) checked="checked" @endif>
                        <span class="xe-input-helper"></span>
                        <span class="xe-label-text">{{xe_trans('board::secretPost')}}</span>
                    </label>
                    @endif

                    @if($isManager === true)
                    <label class="xe-label">
                        <input type="checkbox" name="status" value="{{$item::STATUS_NOTICE}}" @if($item->status == $item::STATUS_NOTICE) checked="checked" @endif>
                        <span class="xe-input-helper"></span>
                        <span class="xe-label-text">{{xe_trans('xe::notice')}}</span>
                    </label>
                    @endif
                </div>
            </div>
            <div class="write_form_btn @if (Auth::check() === false) nologin @endif">
                @if(!app('request')->isMobile())
                    <span class="xe-btn-group">
                        <button type="button" class="xe-btn xe-btn-secondary btn_temp_save __xe_temp_btn_save">{{ xe_trans('xe::draftSave') }}</button>
                        <button type="button" class="xe-btn xe-btn-secondary xe-dropdown-toggle" data-toggle="xe-dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                            <span class="xe-sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="xe-dropdown-menu">
                            <li><a href="#" class="__xe_temp_btn_load">{{ xe_trans('xe::draftLoad') }}</a></li>
                        </ul>
                    </span>
                    <button type="button" class="xe-btn xe-btn-normal bd_btn btn_preview __xe_btn_preview">{{ xe_trans('xe::preview') }}</button>
                    <button type="submit" class="xe-btn xe-btn-primary bd_btn btn_submit __xe_btn_submit">{{ xe_trans('xe::submit') }}</button>
                @else
                    <button type="submit" class="xe-btn xe-btn-primary bd_btn btn_submit __xe_btn_submit paymentButton">{{ xe_trans('xe::submit') }}</button>
                @endif
            </div>
        </div>
    </form>
</div>

<script>
    $(function () {
        var form = $('.__board_form');
        var submitting = false
        form.on('submit', function (e) {
            if (submitting) {
                return false
            }

            if (!submitting) {
                form.find('[type=submit]').prop('disabled', true)
                submitting = true
                setTimeout(function () {
                    form.find('[type=submit]').prop('disabled', false)
                }, 5000);
            }
        })

        var draft = $('#xeContentEditor', form).draft({
            key: 'document|' + form.data('instance_id'),
            btnLoad: $('.__xe_temp_btn_load', form),
            btnSave: $('.__xe_temp_btn_save', form),
            // container: $('.draft_container', form),
            withForm: true,
            apiUrl: {
                draft: {
                    add: xeBaseURL + '/draft/store',
                    update: xeBaseURL + '/draft/update',
                    delete: xeBaseURL + '/draft/destroy',
                    list: xeBaseURL + '/draft',
                },
                auto: {
                    set: xeBaseURL + '/draft/setAuto',
                    unset: xeBaseURL + '/draft/destroyAuto'
                }
            },
            callback: function (data) {
                window.XE.app('Editor').then(function (appEditor) {
                    appEditor.getEditor('XEckeditor').then(function (editorDefine) {
                        var inst = editorDefine.editorList['xeContentEditor']
                        if (inst) {
                            inst.setContents(data.content);
                        }
                    })
                })
            }
        });
    });
</script>
