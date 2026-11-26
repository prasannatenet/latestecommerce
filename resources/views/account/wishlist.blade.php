@extends('layouts.frontend')

@section('title', 'My Wishlist')

@section('content')
    <main class="account-page account-secondary-page">
        <header class="account-title-banner"><h1>My Wishlist</h1><p><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('account.profile') }}">My Account</a><span>/</span>My Wishlist</p></header>
        <div class="account-container account-layout">
            @include('account.partials.sidebar', ['activeAccountPage' => 'wishlist'])
            <section class="account-main-panel" aria-label="Saved products">
                <div class="account-panel-heading"><div><p class="account-eyebrow">YOUR SAVED PIECES</p><h2>My Wishlist</h2></div><span>{{ $wishlistItems->count() }} items</span></div>
                @if ($wishlistItems->isEmpty())
                    <div class="account-orders-empty"><h3>Your wishlist is empty</h3><p>Save your favourite pieces and they’ll appear here.</p><a href="{{ route('storefront.products') }}">Explore jewellery</a></div>
                @else
                    <div class="account-wishlist-grid">
                        @foreach ($wishlistItems as $wishlistItem)
                            @php
                                $product = $wishlistItem->product;
                                $image = $product->primary_image ?: $product->images->firstWhere('is_primary', true)?->path ?: $product->images->first()?->path;
                            @endphp
                            <article class="account-wishlist-card">
                                <a href="{{ url('/product/' . $product->slug) }}" class="account-wishlist-image-link"><img src="{{ $image ? asset('storage/' . ltrim($image, '/')) : asset('frontend/images/product-1.jpeg') }}" alt="{{ $product->name }}" loading="lazy"></a>
                                <div class="account-wishlist-details"><h2><a href="{{ url('/product/' . $product->slug) }}">{{ $product->name }}</a></h2><p>₹{{ number_format($product->effectivePrice(), 2) }}</p><a class="account-button" href="{{ url('/product/' . $product->slug) }}">View product</a></div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </main>
@endsection
