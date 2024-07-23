@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.update.header')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/update.scss')
@endpush

@push('footer')
    <script>
        var updateContent = `{!! admin()->update()->latestChanges() !!}`;
    </script>
    @at('https://cdn.jsdelivr.net/npm/marked/marked.min.js')
    @at('Core/Admin/Http/Views/assets/js/pages/update.js')
@endpush

@push('content')
    <div class="update-content">
        @if (admin()->update()->needUpdate())
            <div class="update-container">
                <div class="update-max">
                    <div class="gradient-text update-container-title">@t('admin.update.available')</div>
                    <div class="update-container-tags">
                        <div class="update-container-tags-tag">
                            {{ \Flute\Core\App::VERSION }}
                        </div>
                        <i class="ph ph-arrow-right"></i>
                        <div class="update-container-tags-tag new-tag">
                            {{ admin()->update()->latestVersion() }}
                        </div>
                    </div>
                </div>
                <div class="update-container-body">
                    {{-- <div class="update-container-body-title">@t('admin.update.whats_changed')</div> --}}
                    <div class="update-container-body-content"></div>
                </div>

                <div class="position-relative row form-group update-notification">
                    <div class="col-sm-12">
                        <div class="admin-notification">
                            <div class="admin-notification-content">
                                <i class="ph ph-warning-circle"></i>
                                <div>
                                    <h4>@t('def.warning')!</h4>
                                    <p>@t('admin.update.warning_desc')</p>
                                </div>
                            </div>
                            <a data-faq="@t('admin.update.faq.title')" data-faq-content="@t('admin.update.faq.content')">@t('def.learn_more')</a>
                        </div>
                    </div>
                </div>

                <div class="update-container-button" id="updateButton">
                    <i class="ph ph-confetti"></i>
                    @t('def.update')
                </div>
            </div>

            <div class="bg-update"></div>
            <div class="update-modal">
                <div class="update-modal-body">
                    <div id="update_success" hidden>
                        <div class="update-check"></div>
                        <div class="update-modal-body">
                            <p>@t('def.success')</p>
                            <small>@t('admin.update.success_desc')</small>
                        </div>
                    </div>
                    <div id="update_loading">
                        <div class="update-loader"></div>
                        <div class="update-modal-body">
                            <p>@t('admin.update.please_wait')</p>
                            <small>@t('admin.update.please_wait_desc')</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-update-modal"></div>
        @else
            <div class="update-container no-need">
                <div class="gradient-text update-container-title">@t('admin.update.no_updates')</div>
            </div>
            <div class="bg-update"></div>
        @endif
    </div>
@endpush
