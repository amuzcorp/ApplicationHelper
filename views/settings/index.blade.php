<div class="panel">
    <div class="panel-heading">
    <h4>API LIST</h4>
    </div>
    <div class="panel-body">
    <table class="xe-table">
        <thead>
        <tr>
            <th>Allow Method</th>
            <th>Reource</th>
            <th>
                Route Name<br />
                Uses
            </th>
        </tr>
        </thead>
        @foreach($_routes as $id => $route)
            <tr>
                <td>
                    @foreach($route->methods as $method)
                        <span class="badge badge-{{$method_colors[$method]}}">{{ $method }}</span>
                    @endforeach
                </td>
                <td>
                    <a href="/{{ $route->uri }}" target="_blank" class="btn btn-sm btn-outline-primary">{{ $route->uri }}</a>
                </td>
                <td>
                    <strong>{{ $route->as }}</strong><br />
                    {{ $route->use_method }}
                </td>
            </tr>
        @endforeach
    </table>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <h4>Route Instance LIST</h4>
    </div>
    <div class="panel-body">
    <table class="xe-table">
        <thead>
        <tr>
            <th>Allow Method</th>
            <th>Reource</th>
            <th>
                Route Name<br />
                Uses
            </th>
        </tr>
        </thead>
        @foreach($_instance_routes as $module => $routes)
            <tr>
                <th colspan="4">{{$module}}</th>
            </tr>
            @foreach($routes as $route)
                <tr>
                    <td>
                        @foreach($route->methods as $method)
                            <span class="badge badge-{{$method_colors[$method]}}">{{ $method }}</span>
                        @endforeach
                    </td>
                    <td>
                        <span class="btn btn-sm btn-outline-info">{{ $route->uri }}</span>
                    </td>
                    <td>
                        <strong>{{ $route->as }}</strong><br />
                        {{ $route->use_method }}
                    </td>
                </tr>
            @endforeach
        @endforeach
    </table>
    </div>
</div>
