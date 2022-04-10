@php
    $link = route('banner::group.edit', ['group_id' => '']);
    $bannerGroupEditLink = str_replace('//edit', '', $link);
    $idx = count($main_banner) + count($content_banner);
@endphp

@section('page_title')
    <h2>{{$title}}</h2>
@endsection

@section('page_description')
    <small>{!! $description !!}</small>
@endsection
<input type="hidden" name="idx" value="{{$idx}}" />
<input type="hidden" name="str" value="{{$str}}" />
<input type="hidden" id="menuDefault" value="{{$menus[0]->id}}" />
<div class="container-fluid container-fluid--part">
    <div class="panel-group" role="tablist" aria-multiselectable="true">
        <div class="panel">
            <div class="panel-heading">
                <div class="pull-left">
                    <h3 class="panel-title">{{$title}}</h3>
                </div>
            </div>
            <div class="panel-collapse collapse in">
                <form method="post" action="{{ route('application_helper.settings.banner_config.update') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                    <input type="hidden" name="menus" @if($app_config) value="{{json_enc($menus->toArray())}}" @else value='[{"id":"default","title":"\uae30\ubcf8"}]' @endif/>
                    <input type="hidden" name="banner_list" @if($app_config) value="{{json_enc($main_banner)}}" @else value="[]" @endif/>
                    <input type="hidden" name="content_banner_list" @if($app_config) value="{{json_enc($content_banner)}}" @else value="[]" @endif/>
                    <input type="hidden" name="banner_target" value="main">
                    <div class="panel-body">
                        <div class="container-fluid container-fluid--part">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="panel-group">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="pull-left">
                                                    <h3 class="panel-title">
                                                        앱 메인 배너 선택
                                                    </h3>
                                                </div>
                                                <div class="pull-right">
                                                    <a class="btn btn-info"
                                                       data-toggle="modal"
                                                       data-animation="bounce"
                                                       data-target=".callEventBanner"
                                                       data-backdrop="static"
                                                       data-keyboard="false" onclick="targetBanner('main')">배너 선택</a>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">제목</th>
                                                        <th scope="col">배너이미지</th>
                                                        <th scope="col">그룹</th>
                                                        <th scope="col">슬라이드시간(초)</th>
                                                        <th scope="col">생성일</th>
                                                        <th scope="col">관리</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="selected_banner_items">
                                                    @foreach($main_banner as $banner)
                                                        <tr id="main_{{$banner['idx']}}">
                                                            <td>{{$banner['title']}}</td>
                                                            <td><img src="{{$banner['image_path']}}" style="width:150px;"></td>
                                                            <td>
                                                                <input type="text" value="{{$banner['menu']}}" onchange="getItemMenu(this,'{{$banner['idx']}}', 'main')"/>
{{--                                                                <select >--}}
{{--                                                                    @foreach($menus as $menu)--}}
{{--                                                                        <option value="{{$menu->id}}" @if($menu->id == $banner['menu']) selected @endif>{{$menu->title}}</option>--}}
{{--                                                                    @endforeach--}}
{{--                                                                </select>--}}
                                                            </td>
                                                            <td><input type="number" name="{{$banner['id']}}_timer" value="{{$banner['slide_time']}}" onchange="setBannerTimer(this,'{{$banner['idx']}}', 'main',)"></td>
                                                            <td>{{$banner['created_at']}}</td>
                                                            <td>
                                                                <a class="xe-btn xe-btn-xs xe-btn-default" onclick="window.open(this.href, 'bannerEditor', 'directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no');return false" href="{{$bannerGroupEditLink}}/{{$banner['group_id']}}/edit">배너그룹 관리</a>
                                                                <a class="xe-btn xe-btn-xs xe-btn-default" onclick="removeBannerItem('main', '{{$banner['idx']}}')">리스트 삭제</a>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="container-fluid container-fluid--part">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="panel-group">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="pull-left">
                                                    <h3 class="panel-title">
                                                        앱 컨텐츠 배너 선택
                                                    </h3>
                                                </div>
                                                <div class="pull-right">
                                                    <a class="btn btn-info"
                                                       data-toggle="modal"
                                                       data-animation="bounce"
                                                       data-target=".callEventBanner"
                                                       data-backdrop="static"
                                                       data-keyboard="false" onclick="targetBanner('content')">배너 선택</a>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">제목</th>
                                                        <th scope="col">배너이미지</th>
                                                        <th scope="col">그룹</th>
                                                        <th scope="col">슬라이드시간(초)</th>
                                                        <th scope="col">생성일</th>
                                                        <th scope="col">관리</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="selected_content_banner_items">
                                                    @foreach($content_banner as $banner)
                                                        <tr id="content_{{$banner['idx']}}">
                                                            <td>{{$banner['title']}}</td>
                                                            <td><img src="{{$banner['image_path']}}" style="width:150px;"></td>
                                                            <td>
                                                                <input type="text" value="{{$banner['menu']}}" onchange="getItemMenu(this, '{{$banner['idx']}}', 'content')"/>
{{--                                                                <select onchange="getItemMenu(this, '{{$banner['idx']}}', 'content')">--}}
{{--                                                                    @foreach($menus as $menu)--}}
{{--                                                                        <option value="{{$menu->id}}" @if($menu->id == $banner['menu']) selected @endif>{{$menu->title}}</option>--}}
{{--                                                                    @endforeach--}}
{{--                                                                </select>--}}
                                                            </td>
                                                            <td><input type="number" name="{{$banner['id']}}_timer" value="{{$banner['slide_time']}}" onchange="setBannerTimer(this,'{{$banner['idx']}}', 'content')"></td>
                                                            <td>{{$banner['created_at']}}</td>
                                                            <td>
                                                                <a class="xe-btn xe-btn-xs xe-btn-default" onclick="window.open(this.href, 'bannerEditor', 'directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no');return false" href="{{$bannerGroupEditLink}}/{{$banner['group_id']}}/edit">배너그룹 관리</a>
                                                                <a class="xe-btn xe-btn-xs xe-btn-default" onclick="removeBannerItem('content', '{{$banner['idx']}}')">리스트 삭제</a>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button class="btn btn-primary">변경사항 저장</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade callEventBanner" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_title">배너 아이템 선택</h5>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-12">
                            <label>배너 그룹 선택</label>
                            <select class="form-control" onchange="getBannerItems(this)">
                                <option value="">배너 그룹을 선택해주세요</option>
                                @foreach($banner_group as $group)
                                    <option value="{{route('banner::group.edit', ['group_id' => $group->id])}}">{{$group->title}} [ 아이템 수 - {{$group->count}} ]</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>배너 아이템 선택</label>
                    <table class="table">
                        <colgroup>
                            <col style=""/>
                            <col style="width:20%"/>
                            <col style=""/>
                            <col style=""/>
                        </colgroup>
                        <thead>
                        <tr>
                            <th>제목</th>
                            <th>상태</th>
                            <th>생성일</th>
                            <th>관리</th>
                        </tr>
                        </thead>
                        <tbody id="banner_item_list">
                        <tr>
                            <td colspan="4">배너 그룹을 선택해주세요</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a class="btn xe-btn-secondary" data-dismiss="modal">취소</a>
            </div>
        </div>
    </div>
</div>

<script>
    function targetBanner(target) {
        $('input[name=banner_target]').val(target);
        if(target === 'main') {
            document.getElementById('modal_title').innerText = '메인 배너 아이템 선택';
        } else {
            document.getElementById('modal_title').innerText = '컨텐츠 배너 아이템 선택';
        }
    }

    function getBannerItems(item) {
        if(item.value == '') return;
        XE.ajax({
            type: 'get',
            dataType: 'json',
            url: item.value,
            success: function(response) {
                document.getElementById('banner_item_list').innerHTML = '';
                var items = response.items;
                var str = '';
                if(items.length === 0) {
                    str = `
                        <tr>
                            <td colspan="4">배너 그룹에 등록된 아이템이 없습니다</td>
                        </tr>`;
                } else {
                    for (let item of items) {
                        str += `
                            <tr>
                                <td>${item.title}</td>
                                <td>${item.status}</td>
                                <td>${item.created_at}</td>
                                <td>
                                    <a class="xe-btn xe-btn-xs xe-btn-default" onclick="selectBannerItem('${item.id}', '${item.group_id}')">배너 선택</a>
                                </td>
                            </tr>
                        `;
                    }

                }
                document.getElementById('banner_item_list').innerHTML = str;
            },
            error: function(response) {
            }
        });

    }
    function selectBannerItem(id, group_id) {
        var Banner_target = $('input[name=banner_target]').val();
        // if(!document.getElementById(Banner_target + '_' + id)) {
        //  // ajax
        // }
        var banner_list = '';
        XE.ajax({
            type: 'get',
            dataType: 'json',
            data: {item_id : id},
            url: '{{ route('application_helper.get.banner.item') }}',
            success: function (response) {
                var item = response.item;
                var idx = (+$('input[name=idx]').val()) + 1;
                $('input[name=idx]').val(idx);
                var str_text = $('input[name=str]').val();
                var menus = JSON.parse($('input[name=menus]').val());
                var defaultMenuId = $('input[name=menuDefault]').val();
                var str = '';
                if(Banner_target === 'main') { str += `<tr id="${str_text + '_' + idx}">`; }
                else { str += `<tr id="content_${str_text + '_' + idx}">`; }
                //<select onchange="getItemMenu(this,'${str_text + '_' + idx}', '${Banner_target}')">`;
                // for(let i = 0; i < menus.length; i++) {
                //     if(defaultMenuId === menus[i].id) {
                //         str += `<option value="${menus[i]['id']}" selected>${menus[i]['title']}</option>`;
                //     } else {
                //         str += `<option value="${menus[i]['id']}">${menus[i]['title']}</option>`;
                //     }
                // }
                // str += `</select>
                str += `
                            <td>${item.title}</td>
                            <td><img src="${item.image.path}" style="width:150px;"></td>
                            <td>
                                <input type="text" value="" onchange="getItemMenu(this,'${str_text + '_' + idx}', '${Banner_target}')"/>
                            </td>
                            <td><input type="number" name="${item.id}_timer" value="0" onchange="setBannerTimer(this,'${str_text + '_' + idx}', '${Banner_target}')"></td>
                            <td>${item.created_at}</td>
                            <td>
                                <a class="xe-btn xe-btn-xs xe-btn-default" onclick="window.open(this.href, 'bannerEditor', 'directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no');return false" href="{{$bannerGroupEditLink}}/${group_id}/edit">배너그룹 관리</a>`;
                if(Banner_target === 'main') {
                    str += `<a class="xe-btn xe-btn-xs xe-btn-default" onclick="removeBannerItem( 'main', '${str_text + '_' + idx}')">리스트 삭제</a>`;
                } else {
                    str += `<a class="xe-btn xe-btn-xs xe-btn-default" onclick="removeBannerItem( 'content', '${str_text + '_' + idx}')">리스트 삭제</a>`;
                }

                str += `    </td>
                        </tr>
                    `;

                if(Banner_target === 'main') {
                    banner_list = JSON.parse($('input[name=banner_list]').val());
                    $('#selected_banner_items').append(str);
                } else {
                    banner_list = JSON.parse($('input[name=content_banner_list]').val());
                    $('#selected_content_banner_items').append(str);
                }

                banner_list.push({
                    idx : str_text + '_' + idx,
                    id : item.id,
                    group_id : item.group_id,
                    title : item.title,
                    image_path : item.image.path,
                    image_id : item.image.id,
                    created_at : item.created_at,
                    slide_time : 0,
                    content : item.content,
                    link : item.link,
                    link_target : item.link_target,
                    group : item.group,
                    menu: defaultMenuId
                });

                if(Banner_target === 'main') $('input[name=banner_list]').val(JSON.stringify(banner_list));
                else $('input[name=content_banner_list]').val(JSON.stringify(banner_list));

            }
        });
    }
    function getItemMenu(item, idx, target) {
        var banner_list = '';

        if(target === 'main') banner_list = JSON.parse($('input[name=banner_list]').val());
        else banner_list = JSON.parse($('input[name=content_banner_list]').val());
        for(let banner of banner_list) {
            if(banner.idx === idx) {
                banner.menu = item.value;
                break;
            }
        }
        if(target === 'main') $('input[name=banner_list]').val(JSON.stringify(banner_list));
        else $('input[name=content_banner_list]').val(JSON.stringify(banner_list));
    }

    function setBannerTimer(item, idx, target) {
        var banner_list = '';

        if(target === 'main') banner_list = JSON.parse($('input[name=banner_list]').val());
        else banner_list = JSON.parse($('input[name=content_banner_list]').val());

        for(let banner of banner_list) {
            if(banner.idx === idx) {
                banner.slide_time = item.value;
                break;
            }
        }

        if(target === 'main') $('input[name=banner_list]').val(JSON.stringify(banner_list));
        else $('input[name=content_banner_list]').val(JSON.stringify(banner_list));
    }
    function removeBannerItem(Banner_target, idx) {
        $('#' + Banner_target + '_' + idx).remove();

        var banner_list = '';

        if(Banner_target === 'main') banner_list = JSON.parse($('input[name=banner_list]').val());
        else banner_list = JSON.parse($('input[name=content_banner_list]').val());

        for(let i = 0; i < banner_list.length; i++) {
            if(banner_list[i].idx === idx) {
                banner_list.splice(i, 1);
                break;
            }
        }

        if(Banner_target === 'main') $('input[name=banner_list]').val(JSON.stringify(banner_list));
        else $('input[name=content_banner_list]').val(JSON.stringify(banner_list));
    }
</script>
