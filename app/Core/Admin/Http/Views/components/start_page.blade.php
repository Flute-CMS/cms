<div class="start-page">
    <div class="sp-bg"></div>
    <div class="start-page-container">
        <div class="welcome">
            @t('admin.hello', [
                "%name%" => user()->name
            ])
        </div>
        <div class="start-page-container-items">
            @foreach (admin()->sidebar()->categories() as $sectionName)
                @foreach (admin()->sidebar()->{$sectionName} as $item)
                    @if (
                        ((isset($item['permission']) && user()->hasPermission($item['permission'])) || !isset($item['permission'])) &&
                            $item['title'] !== 'admin.home.title' &&
                            isset($item['url']))
                        <a href="{{ $item['url'] }}" class="item">
                            <i class="ph {{ $item['icon'] }}"></i>
                            <div class="name-desc">
                                <h3>{{ __($item['title']) }}</h3>
                                <p>@t(str_replace('.title', '.short_desc', $item['title']))</p>
                            </div>
                        </a>
                    @endif
                @endforeach
            @endforeach
        </div>
    </div>
</div>
