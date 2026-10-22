<x-app-layout>
    <x-slot name="header">{{ $institutional ? __('Institutional Archive') : __('My request archive') }}</x-slot>
    @include('requests.partials.archive-workspace')
</x-app-layout>
