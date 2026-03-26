@if (!empty(social()->getAll()))
    @php
        $socials = collect(social()->getAll());
        $count = $socials->count();
    @endphp

    <div class="auth__socials @if ($count > 1) auth__socials--row @endif">
        @foreach (social()->getAll() as $key => $item)
            <a href="{{ url('social/' . $key) }}?popup=1"
               data-connect="{{ url('social/' . $key) }}?popup=1"
               data-tooltip="{{ __('auth.social.auth_via', [':social' => $item['entity']->key]) }}"
               data-tooltip-conf="bottom"
               class="auth__socials-item @if ($count > 1) auth__socials-item--icon-only @endif">
                <x-icon :path="$item['entity']->icon" />
                <span>@t('auth.social.auth_via', [':social' => $item['entity']->key])</span>
            </a>
        @endforeach
    </div>
@endif
