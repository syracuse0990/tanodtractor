<div>
    <div class="row">
        <div class="col-md-12 text-end">
            @foreach ($model->stateOptions() as $key => $value)
                @if ($model->state_id == $key)
                    @continue
                @endif
                <a href='{{ route($model->state_url, ['id' => $model->id, 'state_id' => $key]) }}'
                    class="btn btn-{{ $model->getColor($key) }} text-white btn-sm rounded-pill px-3 state-icon">{{ $value }}</a>
            @endforeach
        </div>
    </div>
</div>
