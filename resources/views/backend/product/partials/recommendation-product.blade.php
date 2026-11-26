<div data-recommendation-selected="{{ $product->id }}">
    <label class="d-flex align-items-center gap-2 border rounded p-2" style="cursor:pointer;">
        <input type="checkbox" name="{{ $inputName }}" value="{{ $product->id }}" checked data-recommendation-checkbox>
        @php
            $imagePath = $product->primary_image ?: $product->images->first()?->path;
        @endphp
        <img src="{{ $imagePath ? asset('storage/' . ltrim($imagePath, '/')) : asset('frontend/images/product-1.jpeg') }}" alt="" width="44" height="44" style="object-fit:cover;border-radius:6px;">
        <span class="flex-grow-1 small">{{ $product->name }}</span>
        <span class="small text-muted">₹{{ number_format($product->effectivePrice(), 2) }}</span>
    </label>
</div>
