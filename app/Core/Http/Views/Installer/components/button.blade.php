<button class="installer-btn animate__animated" id="{{ $id ?? 'sendBtn' }}" @if($disabled ?? false === true) disabled @endif>
    <p class="animate__animated">{{ $text ?? __('Далее') }}</p>
    <i class="ph ph-arrow-right"></i>
</button>
