@component('mail::message')

{{ __('The API token ":token" will expire in 7 days!', ['token' => $tokenName]) }}

{{ __('Please make sure to create a new API token and use the new one instead before it expires to avoid any disruptions in service.') }}

{{ __('You can create a new API token in your profile:') }}

@component('mail::button', ['url' => $profileUrl])
    {{ __('Go to your profile') }}
@endcomponent

@endcomponent
