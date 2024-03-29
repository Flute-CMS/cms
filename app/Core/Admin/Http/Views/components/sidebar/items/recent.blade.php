<!-- Recent Menu -->
@if (admin()->sidebar()->recent)
    @push('admin::recent-sidebar')
        <div class="recent-menu">
            <div class="title">
                <div>
                    <i class="ph ph-arrow-clockwise"></i>
                    @t('admin.recent')
                </div>
                <a data-open="@t('def.open')" data-hide="@t('def.hide')">@t(cookie('recent_hide') === 'true' ? 'def.open' : 'def.hide')</a>
            </div>
            <div class="items @if(cookie('recent_hide') === 'true') hidden @endif">
                @foreach (admin()->sidebar()->recent as $title => $path)
                    <div class="item">
                        <a href="{{ url($path) }}">{{ __($title) }}</a>
                        <i class="ph ph-x" data-delete="{{ $title }}"></i>
                    </div>
                @endforeach
            </div>
        </div>
    @endpush
@endif
