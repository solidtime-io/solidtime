@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(trim($slot) === 'solidtime')
<img src="{{ asset('images/solidtime-logo.png') }}" srcset="{{ asset('images/solidtime-logo.svg') }}" class="logo" alt="solidtime Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
