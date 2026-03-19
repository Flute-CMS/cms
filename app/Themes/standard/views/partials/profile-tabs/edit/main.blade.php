@yoyo('profile-edit-main')

@if (config('auth.two_factor.enabled'))
    @yoyo('profile-two-factor')
@endif

@yoyo('profile-delete-account')
