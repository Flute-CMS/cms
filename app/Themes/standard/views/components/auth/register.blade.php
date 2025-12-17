<form class="auth-form @if (empty(social()->getAll())) mt-4 @endif">
    {{-- Slot: before fields --}}
    @if(isset($formEvent))
        {!! $formEvent->renderSlot('beforeFields') !!}
    @endif

    <x-forms.field class="mb-3">
        <x-forms.label for="name" required>@t('auth.name'):</x-forms.label>
        <x-fields.input type="text" name="name" id="name" value="{{ $name }}" required autofocus
            placeholder="{{ __('auth.name_placeholder') }}" />
    </x-forms.field>

    <x-forms.field class="mb-3">
        <x-forms.label for="login" required>@t('auth.user_login'):</x-forms.label>
        <x-fields.input type="text" name="login" id="login" value="{{ $login }}" required
            placeholder="{{ __('auth.user_login_placeholder') }}" />
    </x-forms.field>

    <x-forms.field class="mb-3">
        <x-forms.label for="email" required>@t('auth.email'):</x-forms.label>
        <x-fields.input type="email" name="email" id="email" value="{{ $email }}" required
            placeholder="{{ __('auth.email_placeholder') }}" />
    </x-forms.field>

    <x-forms.field class="mb-3">
        <x-forms.label for="password" required>@t('auth.password'):</x-forms.label>
        <x-fields.input type="password" name="password" id="password" value="{{ $password }}" toggle="true"
            required placeholder="{{ __('auth.password_placeholder') }}" />
    </x-forms.field>

    <x-forms.field class="mb-4">
        <x-forms.label for="password_confirmation" required>@t('auth.password_confirmation'):</x-forms.label>
        <x-fields.input type="password" name="password_confirmation" id="password_confirmation"
            value="{{ $password_confirmation }}" toggle="true" required
            placeholder="{{ __('auth.password_confirmation_placeholder') }}" />
    </x-forms.field>

    {{-- Slot: after fields --}}
    @if(isset($formEvent))
        {!! $formEvent->renderSlot('afterFields') !!}
    @endif

    @include('flute::components.captcha', ['action' => 'register'])

    {{-- Slot: before submit --}}
    @if(isset($formEvent))
        {!! $formEvent->renderSlot('beforeSubmit') !!}
    @endif

    <div>
        <x-button yoyo:post="register" yoyo:on="click" type="accent" class="w-100" withLoading>
            @t('def.register')
            <x-icon path="ph.regular.arrow-right" />
        </x-button>
    </div>

    {{-- Slot: after submit --}}
    @if(isset($formEvent))
        {!! $formEvent->renderSlot('afterSubmit') !!}
    @endif
</form>
