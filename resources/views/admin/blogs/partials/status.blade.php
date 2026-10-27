{{-- Draft / Published / Scheduled badge. Expects: $blog. --}}
@php($state = $blog->displayState())
<x-admin.badge :color="$state['color']">{{ $state['label'] }}</x-admin.badge>
