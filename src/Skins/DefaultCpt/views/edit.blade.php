{{ XeFrontend::rule('board', $rules) }}

{{ XeFrontend::js('assets/core/common/js/draft.js')->appendTo('head')->load() }}
{{ XeFrontend::css('assets/core/common/css/draft.css')->load() }}

@if($config->get('useTag') === true)
    {{ XeFrontend::js('plugins/board/assets/js/BoardTags.js')->appendTo('body')->load() }}
@endif

<div class="board_write">
    <form method="post" id="board_form" class="__board_form" action="{{ $cptUrlHandler->get('update', app('request')->query->all()) }}" enctype="multipart/form-data" data-rule="board" data-rule-alert-type="toast" data-instance_id="{{$instanceId}}">
        <input type="hidden" name="_token" value="{{{ Session::token() }}}" />
        <input type="hidden" name="id" value="{{$item->id}}" />
        <input type="hidden" name="queryString" value="{{ http_build_query(Request::except('parent_id')) }}" />

        @foreach ($cptConfig['formColumns'] as $columnName)
            @if($columnName === 'title')
                <div class="write_header">
                    <div class="write_category">
                        @if(count($taxonomies) > 0)
                            @foreach ($taxonomies as $taxonomy)
                                <label>
                                    {{ xe_trans($taxonomy->name) }}
                                </label>
                                <div>
                                    <div data-required-title="{{ xe_trans($taxonomy->name) }}">
                                        {!! uio('uiobject/df@taxo_select', [
                                            'name' => app('overcode.df.taxonomyHandler')->getTaxonomyItemAttributeName($taxonomy->id),
                                            'label' => xe_trans($taxonomy->name),
                                            'template' => $taxonomy->extra->template,
                                            'items' => app('overcode.df.taxonomyHandler')->getCategoryItemsTree($taxonomy->id),
                                            'value' => isset($category_items[$taxonomy->id]) ? $category_items[$taxonomy->id] : ''
                                        ]) !!}
                                    </div>
                                </div>
                                <br>
                            @endforeach
                        @endif
                    </div>
                    <div class="write_title">
                        {!! uio('uiobject/df@doc_title', [
                            'title' => Request::old('title', $item->title),
                            'slug' => $item->getSlug(),
                            'titleClassName' => 'bd_input',
                            'titleName' => '타이틀',
                            'cpt_id' => $config->get('cpt_id')
                        ]) !!}
                    </div>
                </div>
            @elseif($columnName === 'content')
                <div class="write_body">
                    <div class="write_form_editor">
                        {!! editor($config->get('cpt_id'), [
                            'content' => Request::old('content', $item->content),
                            'cover' => true
                        ], $item->id, $thumb ? $thumb->df_thumbnail_file_id : null) !!}
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
                        {!! df_edit('documents_'. $config->get('cpt_id'), $columnName, $item->getAttributes()) !!}
                    </div>
                @endif
            @endif
        @endforeach

        <div class="dynamic-field">
            @foreach ($fieldTypes as $dynamicFieldConfig)
                @if (in_array($dynamicFieldConfig->get('id'), $cptConfig['formColumns']) === false && ($fieldType = XeDynamicField::getByConfig($dynamicFieldConfig)) != null && $dynamicFieldConfig->get('use') == true)
                    <div class="__xe_{{$dynamicFieldConfig->get('id')}} __xe_section">
                        {!! df_edit($dynamicFieldConfig->get('group'), $dynamicFieldConfig->get('id'), $item->getAttributes()) !!}
                    </div>
                @endif
            @endforeach
        </div>

        <div class="draft_container"></div>

        <!-- 비로그인 -->
        <div class="write_footer">
            <div class="write_form_input">
                @if ($item->user_type == $item::USER_TYPE_GUEST)
                    <div class="xe-form-inline">
                        <input type="text" name="writer" class="xe-form-control" placeholder="{{ xe_trans('xe::writer') }}" title="{{ xe_trans('xe::writer') }}" value="{{ Request::old('writer', $item->writer) }}">
                        <input type="password" name="certify_key" class="xe-form-control" placeholder="{{ xe_trans('xe::password') }}" title="{{ xe_trans('xe::password') }}" data-valid-name="{{xe_trans('xe::certify_key')}}">
                        <input type="email" name="email" class="xe-form-control" placeholder="{{ xe_trans('xe::email') }}" title="{{ xe_trans('xe::email') }}" value="{{ Request::old('email', $item->email) }}">
                    </div>
                @endif
            </div>
            <div class="write_form_option">
                <div class="xe-form-inline">
                    @if($config->get('comment') === true)
                        <label class="xe-label">
                            <input type="checkbox" name="allow_comment" value="1" checked="checked">
                            <span class="xe-input-helper"></span>
                            <span class="xe-label-text">{{xe_trans('board::allowComment')}}</span>
                        </label>
                    @endif

                    @if (Auth::check() === true)
                        <label class="xe-label">
                            <input type="checkbox" name="use_alarm" value="1" @if($config->get('newCommentNotice') == true) checked="checked" @endif >
                            <span class="xe-input-helper"></span>
                            <span class="xe-label-text">{{xe_trans('board::useAlarm')}}</span>
                        </label>
                    @endif

                    @if($config->get('secretPost') === true)
                        <label class="xe-label">
                            <input type="checkbox" name="display" value="{{\Xpressengine\Document\Models\Document::DISPLAY_SECRET}}">
                            <span class="xe-input-helper"></span>
                            <span class="xe-label-text">{{xe_trans('board::secretPost')}}</span>
                        </label>
                    @endif

                </div>
            </div>
            <div class="write_form_btn @if (Auth::check() === false) nologin @endif">
                <!-- Split button -->
                <span class="xe-btn-group">
                    <button type="button" class="xe-btn xe-btn-secondary __xe_temp_btn_save">{{ xe_trans('xe::draftSave') }}</button>
                    <button type="button" class="xe-btn xe-btn-secondary xe-dropdown-toggle" data-toggle="xe-dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="xe-sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="xe-dropdown-menu">
                        <li><a href="#" class="__xe_temp_btn_load">{{ xe_trans('xe::draftLoad') }}</a></li>
                    </ul>
                </span>
                <button type="button" class="xe-btn xe-btn-normal bd_btn btn_preview __xe_btn_preview">{{ xe_trans('xe::preview') }}</button>
                <button type="submit" class="xe-btn bd_btn btn_submit __xe_btn_submit">{{ xe_trans('xe::submit') }}</button>
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
