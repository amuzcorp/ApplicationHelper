<div class="title">{{ $title }}</div>
<h4>API LIST</h4>
<table>
@foreach($resources->getRoutes() as $id => $action)
    @php
        $middleware = is_array(array_get($action->action,'middleware',[])) ? array_get($action->action,'middleware',[]) : [];
    @endphp
    @if(!in_array('settings', $middleware) && array_get($action->action,'module') == null && array_get($action->action,'prefix') != "_debugbar")
    <tr>
        <td>
            {{$id}}
        </td>
        <td>
        @foreach($action->methods as $method)
            <span class="badge">{{ $method }}</span>
        @endforeach
        </td>
        <td>
            {{ $action->uri }}
        </td>
        <td>
            {{ array_get($action->action,'as') }}
        </td>
    </tr>
    @endif
@endforeach
</table>

<h4>Route Instance LIST</h4>
<table>
@foreach($resources->getRoutes() as $id => $action)
    @php
        $middleware = is_array(array_get($action->action,'middleware',[])) ? array_get($action->action,'middleware',[]) : [];
    @endphp
    @if(!in_array('settings', $middleware) && array_get($action->action,'module') != null && array_get($action->action,'prefix') != "_debugbar")
    <tr>
        <td>
            {{$id}}
        </td>
        <td>
            {{$action->action['module']}}
        </td>
        <td>
        @foreach($action->methods as $method)
            <span class="badge">{{ $method }}</span>
        @endforeach
        </td>
        <td>
            {{ $action->uri }}
        </td>
        <td>
            {{ array_get($action->action,'as') }}
        </td>
    </tr>
    @endif
@endforeach
</table>
