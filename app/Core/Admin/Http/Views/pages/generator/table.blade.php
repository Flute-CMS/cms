@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __($title)]),
])

@if ($stylesPath)
    @push('header')
        @at($stylesPath)
    @endpush
@endif

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t($header)</h2>
            <p>@t($description)</p>
        </div>
        @if ($withAddBtn)
            <div>
                <a href="{{ url($btnAddPath) }}" class="btn size-s outline">
                    @t('def.add')
                </a>
            </div>
        @endif
    </div>

    {!! $content !!}
@endpush

@if ($scriptsPath)
    @push('footer')
        @at($scriptsPath)
    @endpush
@endif
