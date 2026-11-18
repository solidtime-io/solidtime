@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if($whitelabel->getLogoUrl() !== null)
<img src="{{ $whitelabel->getLogoUrl() }}" @if($whitelabel->getLogoSvgUrl() !== null) srcset="{{ $whitelabel->getLogoSvgUrl() }}" @endif alt="{{ $whitelabel->getName() }}" style="height: 40px; max-height: 40px; width: auto; max-width: 240px;">
@elseif($whitelabel->getLogoSvgUrl() !== null)
{{-- Many email clients do not display SVG, without a PNG or JPEG logo the name is shown instead --}}
{{ $whitelabel->getName() }}
@else
<img src="{{ asset('images/solidtime-logo.png') }}" srcset="{{ asset('images/solidtime-logo.svg') }}" class="logo" alt="solidtime Logo">
@endif
</a>
</td>
</tr>
