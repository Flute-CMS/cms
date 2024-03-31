@push('footer')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="footer-container">
                    <div class="project-name">
                        <h1>{{ app('app.name') }}</h1>

                        @if (!empty(footer()->socials()->all()))
                            <div class="social_footer">
                                <p>@t('def.socials')</p>

                                <div class="social_footer_container">
                                    @foreach (footer()->socials()->all() as $social)
                                        <a href="{{ $social->url }}" target="_blank" data-tooltip="{{ $social->name }}">
                                            {!! $social->icon !!}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    @if (!empty(footer()->all()))
                        <div class="d-flex justify-content-center footer_links">
                            @foreach (footer()->all() as $key => $item)
                                <div class="footer_link">
                                    <p>{!! $item['title'] !!}</p>

                                    <ul class="footer_items">
                                        @foreach ($item['children'] as $child_item)
                                            <li class="footer_item">
                                                <a href="{{ $child_item['url'] }}"
                                                    @if ($child_item['new_tab']) target="_blank" @endif>
                                                    {!! $child_item['title'] !!}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>

                                    @if (isset(footer()->all()[$key + 1]))
                                        <div class="right_line"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (config('app.flute_copyright'))
                        <a href="https://github.com/Flute-CMS/cms" target="_blank"
                            class="powered_by_footer">
                            <div>
                                <p class="powered_by">Powered by</p>
                                <h3>Flute Engine</h3>
                            </div>
                            <img src="@asset('assets/img/flute_logo.svg')" alt="">
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endpush
