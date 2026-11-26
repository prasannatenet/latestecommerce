<div class="cart-drawer-content">
    @forelse ($headerCartItems ?? collect() as $item)
        <div class="cart-drawer-item">
            <img src="{{ $item['image'] }}" alt="" loading="lazy">
            <span class="wishlist-drawer-item-details">
                @if ($item['slug'])
                    <a class="wishlist-drawer-item-name" href="{{ url('/product/' . $item['slug']) }}">{{ $item['name'] }}</a>
                @else
                    <span class="wishlist-drawer-item-name">{{ $item['name'] }}</span>
                @endif
                <span class="cart-drawer-item-quantity">Qty: {{ $item['quantity'] }}</span>
                <span class="wishlist-drawer-item-price">₹ {{ number_format((float) $item['subtotal'], 2) }}</span>
            </span>
            <button
                class="cart-drawer-item-remove"
                type="button"
                aria-label="Remove {{ $item['name'] }} from shopping bag"
                data-cart-remove-url="{{ route('storefront.cart.remove') }}"
                @if ($item['cart_id'])
                    data-cart-id="{{ $item['cart_id'] }}"
                @else
                    data-product-id="{{ $item['product_id'] }}"
                    data-product-variation-id="{{ $item['product_variation_id'] ?? '' }}"
                @endif
            >Remove</button>
        </div>
    @empty
        <p class="wishlist-drawer-empty">Your shopping bag is empty.</p>
    @endforelse
</div>
@if (($headerCartItems ?? collect())->isNotEmpty())
    <div class="cart-drawer-footer">
        <div class="cart-drawer-subtotal">
            <span>Subtotal</span>
            <strong>₹ {{ number_format((float) ($headerCartSubtotal ?? 0), 2) }}</strong>
        </div>
        @if (($cartCrossSellSuggestions ?? collect())->isNotEmpty())
            <section class="cart-drawer-suggestions" aria-label="You may also like">
                <h3>You may also like</h3>
                @foreach ($cartCrossSellSuggestions as $suggestion)
                    <article class="cart-drawer-suggestion">
                        <a class="cart-drawer-suggestion-product" href="{{ url('/product/' . $suggestion['slug']) }}">
                            <img src="{{ $suggestion['image'] }}" alt="" loading="lazy">
                            <span class="cart-drawer-suggestion-details">
                                <span class="cart-drawer-suggestion-name">{{ $suggestion['name'] }}</span>
                                <span class="cart-drawer-suggestion-price">₹ {{ number_format((float) $suggestion['price'], 2) }}</span>
                            </span>
                        </a>
                        <button class="cart-drawer-suggestion-add" type="button"
                            data-cart-add-url="{{ route('storefront.cart.add') }}"
                            data-product-id="{{ $suggestion['id'] }}">Add</button>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endif
