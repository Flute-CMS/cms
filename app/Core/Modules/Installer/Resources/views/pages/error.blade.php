@extends('installer::layout')

@push('content')
    <div class="installer__error text-center flex-center flex-column">
        <h1>{{ $code }}</h1>
        <p>{{ $message }}</p>
    </div>
@endpush