<div class="form-control col-md-12" style="height: Auto">
    {{ Form::label('درخواست', 'درخواست', ['class' => 'col-md-3']) }}
    <div class="col-md-12" style="direction:ltr">
        <pre>{{$request}}))</pre>

    </div>
</div>


<div class="form-control col-md-12" style="height: Auto">
    {{ Form::label('پاسخ', 'پاسخ', ['class' => 'col-md-3']) }}
    <div class="col-md-12" style="direction:ltr">
        <pre> {{$response}} </pre>
        @php $response=json_decode($response); @endphp

    </div>
</div>

@if($step=='create' && $status==201 )
    <button><a href="{{$response->link}}">انتقال به لینک دریافت شده</a></button>
@endif
