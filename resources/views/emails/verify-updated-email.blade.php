@component('mail::message')
{{ __('Please verify your new email address for your :app account.', ['app' => $whitelabel->getName()]) }}

@component('mail::button', ['url' => $verificationUrl])
{{ __('Verify Email Address') }}
@endcomponent

{{ __('If you did not request this change, you may discard this email.') }}
@endcomponent
