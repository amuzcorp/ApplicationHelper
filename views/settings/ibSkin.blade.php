<div class="container-fluid container-fluid--part">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel-group">
                @foreach($skinSections as $section_id => $section)
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h3 class="panel-title">{{ array_get($section,'title') }}</h3>
                            <small>{{ array_get($section,'description') }}</small>
                        </div>
                        <div class="pull-right">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">{{xe_trans('fold')}}</span></a>
                        </div>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        {!! $section['skinSection'] !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
