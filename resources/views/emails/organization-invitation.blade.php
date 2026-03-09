<x-mail::message>
# {{ __('Hello!') }}

{{ __('You have been invited to join the organization **:organization** as an **:role**.', ['organization' => $invitation->organization->name, 'role' => $invitation->role->value]) }}

{{ __('Click the button below to accept the invitation and join the team.') }}

<x-mail::button :url="$acceptUrl">
{{ __('Accept Invitation') }}
</x-mail::button>

{{ __('This invitation will expire on :date.', ['date' => $invitation->expires_at->format('Y-m-d H:i')]) }}

{{ __('If you did not expect this invitation, you can safely ignore this email.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
