@component('mail::message')
{{ __('You have been invited to join the :organization organization!', ['organization' => $invitation->organization->name]) }}

@component('mail::button', ['url' => $acceptUrl])
{{ __('Accept Invitation') }}
@endcomponent

{{ __('If you did not expect to receive an invitation to this organization, you may discard this email.') }}
@endcomponent
