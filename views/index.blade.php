<div class="container">
<div class="title">{{ $title }}</div>
<h4>API LIST</h4>
<table class="xe-table">
    <thead>
    <tr>
        <th>Route ID</th>
        <th>Allow Method</th>
        <th>Reource</th>
        <th>Route Name</th>
    </tr>
    </thead>
    @foreach($_routes as $id => $route)
    <tr>
        <td>
            {{$id}}
        </td>
        <td>
        @foreach($route->methods as $method)
            <span class="badge badge-{{$method_colors[$method]}}">{{ $method }}</span>
        @endforeach
        </td>
        <td>
            <span class="btn btn-sm btn-outline-primary">{{ $route->uri }}</span>
        </td>
        <td>
            {{ array_get($route->action,'as') }}
        </td>
    </tr>
    @endforeach
</table>

<h4>Route Instance LIST</h4>
<table class="xe-table">
    <thead>
    <tr>
        <th>Route ID</th>
        <th>Allow Method</th>
        <th>Reource</th>
        <th>Route Name</th>
    </tr>
    </thead>
    @foreach($_instance_routes as $module => $routes)
        <tr>
            <th colspan="4">{{$module}}</th>
        </tr>
        @foreach($routes as $route)
        <tr>
            <td>
                {{$id}}
            </td>
            <td>
                @foreach($route->methods as $method)
                    <span class="badge badge-{{$method_colors[$method]}}">{{ $method }}</span>
                @endforeach
            </td>
            <td>
                <span class="btn btn-sm btn-outline-info">{{ $route->uri }}</span>
            </td>
            <td>
                {{ array_get($route->action,'as') }}
            </td>
        </tr>
        @endforeach
    @endforeach
</table>
</div>
