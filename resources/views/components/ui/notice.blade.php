@props(['tone' => 'info'])

@php($styles = $tone === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-glowy-champagne bg-glowy-blush text-glowy-plum')
<div role="status" {{ $attributes->merge(['class' => "rounded-2xl border px-4 py-3 text-sm {$styles}"]) }}>{{ $slot }}</div>
