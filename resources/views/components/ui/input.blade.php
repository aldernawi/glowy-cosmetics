@props(['label' => null, 'name'])

<label class="grid gap-2 text-sm font-bold text-glowy-ink">
    @if ($label)<span>{{ $label }}</span>@endif
    <input name="{{ $name }}" {{ $attributes->merge(['class' => 'min-h-11 rounded-xl border-glowy-champagne bg-white px-4 text-right shadow-sm outline-none transition placeholder:text-glowy-muted focus:border-glowy-rose focus:ring-2 focus:ring-glowy-rose/20']) }}>
</label>
