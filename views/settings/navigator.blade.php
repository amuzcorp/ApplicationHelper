@section('page_title')
    <h2>{{ $title }}</h2>
@endsection

@section('page_description')
    <small>{!! $description !!}</small>
@endsection
<table style="display:none;">
<tr id="ah_navigator_template">
    <td>
        <input type="text" name="keys[]" class="form-control" required value="" />
    </td>
    <td>
        <select name="menus[]" class="form-control">
            @foreach($menus as $menu)
                <option value="{{ $menu->id }}">
                    {{ $menu->title }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        저장 후 생성됩니다.
    </td>
    <td>
        <a href="#" class="xe-btn xe-btn-danger-outline" onclick="return removeNavigatorRow(this)">삭제</a>
    </td>
</tr>
</table>

<div class="container-fluid container-fluid--part">
    <div class="panel-group" role="tablist" aria-multiselectable="true">
        <div class="panel">
            <div class="panel-heading">
                <div class="pull-left">
                    <h3 class="panel-title">네비게이터 딜리게이터</h3>
                </div>
            </div>
            <div class="panel-collapse collapse in">
                <form method="post" action="{{ route('application_helper.settings.configSave',['type' => 'navigator']) }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

                    <div class="panel-body">
                        <div class="container-fluid container-fluid--part">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="panel-group">
                                        <div class="panel">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">메뉴키</th>
                                                        <th scope="col">메뉴타입 선택</th>
                                                        <th scope="col">REST API</th>
                                                        <th scope="col">기능</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="ah_navigator_warp">
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td>
                                                                <a href="#" onclick="return addNavigatorRow()" class="xe-btn xe-btn-pimary-outline">추가</a>
                                                            </td>
                                                        </tr>
                                                    @foreach($deliver_menus as $key => $delivered)
                                                        <tr>
                                                            <td>
                                                                <input type="text" name="keys[]" class="form-control" required value="{{ $key }}" />
                                                            </td>
                                                            <td>
                                                                <select name="menus[]" class="form-control">
                                                                @foreach($menus as $menu)
                                                                    <option value="{{ $menu->id }}" @if($delivered == $menu->id) selected @endif>
                                                                        {{ $menu->title }}
                                                                    </option>
                                                                @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('ah::navigator_list',['menu_key'=>$key]) }}">{{ route('ah::navigator_list',['menu_key'=>$key]) }}</a>
                                                            </td>
                                                            <td>
                                                                <a href="#" class="xe-btn xe-btn-danger-outline" onclick="return removeNavigatorRow(this)">삭제</a>
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
<script>
    function removeNavigatorRow(obj){
        $(obj).parent().parent().remove();
    }
    function addNavigatorRow(){
        let html = "<tr>" + $("#ah_navigator_template").html() + "</tr>";
        console.log(html);
        $("#ah_navigator_warp").append(html);
    }
</script>
