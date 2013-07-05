@extends('layout')

@section('content')
    @foreach($users as $user)
        <p>{{ $user->name }}: {{ $user->getReminderEmail() }}</p>
    @endforeach
@stop

