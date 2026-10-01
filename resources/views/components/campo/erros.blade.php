@props(['name'])
@foreach ($errors->get($name) as $erro)
<div class="text-danger small mt-1">{{ $erro }}</div>
@endforeach
