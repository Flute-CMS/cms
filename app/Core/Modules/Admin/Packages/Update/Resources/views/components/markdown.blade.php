<div class="md-content">
    @isset($html)
        {!! $html !!}
    @elseif (isset($markdown))
        {!! markdown()->parse($markdown, false, false) !!}
    @endif
</div>

