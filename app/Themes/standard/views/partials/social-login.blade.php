@if (!empty(social()->getAll()))
    @php
        $socials = collect(social()->getAll());
        $firstSocial = $socials->first();
        $remainingSocials = $socials->skip(1);
        $count = $socials->count();
    @endphp

    <div class="auth__socials @if ($count > 2) withFirst @endif">
        @if ($count === 1)
            <a href="{{ url('social/' . $firstSocial['entity']->key) }}?popup=1" data-connect="{{ url('social/' . $firstSocial['entity']->key) }}?popup=1" class="auth__socials-item primary">
                <x-icon path="{!! $firstSocial['entity']->icon !!}" />
                @t('auth.social.auth_via', [
                    ':social' => $firstSocial['entity']->key,
                ])
            </a>
        @elseif ($count <= 2)
            @foreach (social()->getAll() as $key => $item)
                <a href="{{ url('social/' . $key) }}?popup=1" data-connect="{{ url('social/' . $key) }}?popup=1" class="auth__socials-item">
                    <x-icon path="{!! $item['entity']->icon !!}" />
                    {{ $item['entity']->key }}
                </a>
            @endforeach
        @else
            <a href="{{ url('social/' . $firstSocial['entity']->key) }}?popup=1" data-connect="{{ url('social/' . $firstSocial['entity']->key) }}?popup=1" class="auth__socials-item primary">
                <x-icon path="{!! $firstSocial['entity']->icon !!}" />
                @t('auth.social.auth_via', [
                    ':social' => $firstSocial['entity']->key,
                ])
            </a>

            @foreach ($remainingSocials as $key => $item)
                <a href="{{ url('social/' . $key) }}?popup=1" data-connect="{{ url('social/' . $key) }}?popup=1" class="auth__socials-item">
                    <x-icon path="{!! $item['entity']->icon !!}" />
                    {{ $key }}
                </a>
            @endforeach
        @endif
    </div>
@endif
