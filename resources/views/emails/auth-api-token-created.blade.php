@component('mail::message')

{{ __('A new API token ":token" was created for your account.', ['token' => $tokenName]) }}

{{ __('If you did not create this token, please revoke it in your profile and change your password.') }}

@component('mail::button', ['url' => $profileUrl])
    {{ __('Go to your profile') }}
@endcomponent

@endcomponent
