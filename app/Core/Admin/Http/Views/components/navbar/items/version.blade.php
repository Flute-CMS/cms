@can('admin.system')
    <div class="header_version">
        <div class="version">
            @if (!admin()->update()->needUpdate())
                <div class="update-btn" data-tooltip="@t('admin.check_updates')" data-tooltip-conf="bottom multiline">
                    <i class="ph ph-arrow-clockwise"></i>
                </div>
            @endif
            <p>{{ app()->getVersion() }}</p>
        </div>
        @if (admin()->update()->needUpdate())
            <a href="{{ url('admin/update') }}" data-tab class="gradient-text" id="updateBtn">
                <i class="ph ph-confetti"></i>
                @t('admin.update_available', [':version' => admin()->update()->latestVersion()])
            </a>
        @endif
    </div>
@endcan
