@props(['title', 'description'])

<section {{ $attributes->merge(['class' => 'rounded-3xl border border-dashed border-glowy-champagne bg-white p-8 text-center']) }}>
    <h2 class="text-lg font-bold text-glowy-plum">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-glowy-muted">{{ $description }}</p>
    @if (isset($action))<div class="mt-5">{{ $action }}</div>@endif
</section>
