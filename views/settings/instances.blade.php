@section('page_title')
    <h2>{{ $title }}</h2>
@endsection

@section('page_description')
    <small>{!! $description !!}</small>
@endsection

<div class="container-fluid container-fluid--part">
    <div class="panel-group" role="tablist" aria-multiselectable="true">
        <div class="panel">
            <div class="panel-heading">
                <div class="pull-left">
                    <h3 class="panel-title">인스턴스 옵션 애드온</h3>
                </div>
            </div>
            <div class="panel-collapse collapse in">
                <form method="post" action="{{ route('application_helper.settings.configSave',['type' => 'instances']) }}">
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
                                                        <th scope="col">인스턴스 ID</th>
                                                        <th scope="col">인스턴스 타입</th>
                                                        <th scope="col">슬러그</th>
                                                        <th scope="col">스킨</th>
                                                        <th scope="col">대상 State</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="ah_navigator_warp">
                                                    @foreach($instances as $instance)
                                                        <tr>
                                                            <td>
                                                                {{ $instance->instance_id }}
                                                            </td>
                                                            <td>
                                                                {{ $instance->module }}
                                                            </td>
                                                            <td>
                                                                {{ $instance->url }}
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control" name="skin[{{$instance->instance_id}}]" value="{{ array_get(array_get($instance_configs,$instance->instance_id,[]),'skin') }}" />
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control" name="state[{{$instance->instance_id}}]" value="{{ array_get(array_get($instance_configs,$instance->instance_id,[]),'state') }}" />
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
