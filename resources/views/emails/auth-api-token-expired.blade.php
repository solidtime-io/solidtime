@component('mail::message')

{{ __('The API token ":token" expired.', ['token' => $tokenName]) }}


{{ __('You can create a new API token in your profile:') }}

@component('mail::button', ['url' => $profileUrl])
    {{ __('Go to your profile') }}
@endcomponent

@endcomponent
