@props(['variant' => 'primary', 'type' => 'button'])

@php($styles = $variant === 'primary' ? 'bg-glowy-rose text-white hover:bg-glowy-plum' : 'border border-glowy-rose text-glowy-rose hover:bg-glowy-blush')
<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex min-h-11 items-center justify-center rounded-full px-5 py-2.5 text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-glowy-rose focus:ring-offset-2 {$styles}"]) }}>{{ $slot }}</button>
