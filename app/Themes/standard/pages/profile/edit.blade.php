@extends(tt('layout.blade.php'))

@push('header')
    @at(tt('assets/styles/pages/profile_edit.scss'))
@endpush

@section('title')
    {{ !empty(page()->title) ? page()->title : __('profile.settings_profile') }}
@endsection

@push('profile_edit_sidebar')

    @foreach($mods as $key => $val)
        <a href="{{ url('profile/edit', ['mode' => $key]) }}" 
            @if($active === $key) class="active" @endif    
        >
            <i class="{{ $val['icon'] }}"></i>
            <div class="profile_edit_flex">
                <div class="profile_edit_sidebar_header">{{ __($val['name']) }}</div>
                <div class="profile_edit_sidebar_text">{{ __($val['desc']) }}</div>
            </div>
        </a>
    @endforeach
@endpush

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @flash
        @editor
        
        @stack('container')

        <div class="row gx-3">
            <div class="col-md-3">
                <div class="profile_edit_buttons">
                    @stack('profile_edit_sidebar')
                </div>
            </div>
            <div class="col-md-9">
                @stack('profile_edit_content')
            </div>
        </div>
    </div>
@endpush

@footer