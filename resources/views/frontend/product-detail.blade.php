@extends('layouts.frontend')

@php
    $currentPrice = $defaultVariation?->effectivePrice() ?? $product->effectivePrice();
    $regularPrice = $defaultVariation
        ? (float) $defaultVariation->price
        : $product->regularPrice();
    $discountPercentage = $defaultVariation
        ? $defaultVariation->discountPercentage()
        : $product->discountPercentage();
    $currentSku = $defaultVariation?->sku ?: $product->sku;
    $currentStock = $defaultVariation
        ? (int) $defaultVariation->stock
        : ($product->manage_stock ? (int) $product->total_stock : 1);
    $currentDescription = $defaultVariation?->description ?: $product->description;
    $imageUrl = static fn (?string $path): ?string => $path
        ? asset('storage/' . ltrim($path, '/'))
        : null;
    $imagesForVariation = function ($variation = null) use ($product, $imageUrl): array {
        if ($variation && $variation->images->isNotEmpty()) {
            $paths = $variation->images
                ->sortByDesc('is_primary')
                ->pluck('path');
        } else {
            $paths = $product->images
                ->sortByDesc('is_primary')
                ->pluck('path');

            if ($product->primary_image) {
                $paths->prepend($product->primary_image);
            }
        }

        return $paths
            ->filter()
            ->unique()
            ->map(fn ($path): array => [
                'url' => $imageUrl($path),
                'alt' => $product->name,
            ])
            ->values()
            ->all();
    };
    $galleryImages = $imagesForVariation($defaultVariation);
    $hasGalleryImages = $galleryImages !== [];
    $placeholderImage = asset('frontend/images/product-1.jpeg');
    $productVideos = $product->videos->sortByDesc('is_primary')->values();
    $activeProductVideo = $hasGalleryImages ? null : $productVideos->first();
    if ($galleryImages === []) {
        $galleryImages = [['url' => $placeholderImage, 'alt' => $product->name]];
    }
    $priceAttribute = static fn ($price): string => rtrim(rtrim(number_format((float) $price, 2, '.', ''), '0'), '.');
    $discountAttribute = static fn ($discount): string => $discount === null
        ? '0'
        : rtrim(rtrim(number_format((float) $discount, 2, '.', ''), '0'), '.');
    $reviewsForBrowser = $reviews->map(fn ($review): array => [
        'name' => $review->user?->name ?: 'Customer',
        'text' => $review->comment,
        'rating' => (int) $review->rating,
        'date' => $review->created_at?->toISOString(),
    ]);
@endphp

@section('title', $product->name . ' | ' . (optional($appSetting)->site_name ?: 'Maanyas'))

@section('content')
    <div class="container">
        <section class="product-detail-page" aria-label="Product details">
            <nav class="product-breadcrumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
                @if ($product->category)
                    <a href="{{ url('/category/' . $product->category->slug) }}">{{ $product->category->name }}</a>
                    <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
                @endif
                <span aria-current="page">{{ $product->name }}</span>
            </nav>

            <div class="product-detail-layout">
                <div class="product-gallery" id="productGallery"
                    data-product-images="{{ $variations->isNotEmpty() ? '[]' : json_encode($galleryImages) }}">
                    <div class="product-thumbnails" role="group" aria-label="Choose product image">
                        @foreach ($galleryImages as $index => $image)
                            <button class="product-thumbnail {{ $index === 0 ? 'is-active' : '' }}" type="button"
                                aria-label="View product image {{ $index + 1 }}" aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                                data-image="{{ $image['url'] }}" data-gallery-image="{{ $image['url'] }}"
                                data-alt="{{ $image['alt'] }}">
                                <img src="{{ $image['url'] }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    @foreach ($productVideos as $video)
                        <button class="gallery-thumb-video" type="button"
                            data-video-url="{{ route('product.videos.stream', $video) }}"
                            aria-label="Play product video">
                            <video class="gallery-video-preview" muted playsinline preload="metadata"
                                src="{{ route('product.videos.stream', $video) }}#t=0.1"></video>
                            <span class="material-symbols-outlined" aria-hidden="true">play_circle</span>
                            <span>Product video</span>
                            <span class="thumb-play-badge" aria-hidden="true">▶</span>
                        </button>
                    @endforeach
                    </div>

                    @foreach ($variations as $variation)
                        @foreach ($imagesForVariation($variation) as $image)
                            <span hidden data-variation-id="{{ $variation->id }}"
                                data-gallery-image="{{ $image['url'] }}"
                                data-description="{{ $variation->description ?: $product->description }}"></span>
                        @endforeach
                    @endforeach

                    <div class="product-main-image">
                        <button class="product-image-zoom-trigger" type="button" aria-label="Open product image gallery"
                            @if ($activeProductVideo) style="display:none" @endif>
                            <img id="mainProductImg"
                                class="product-feature-image {{ $activeProductVideo ? 'main-product-img-placeholder' : '' }}"
                                @if ($activeProductVideo) style="display:none" @endif
                                src="{{ $galleryImages[0]['url'] }}"
                                alt="{{ $galleryImages[0]['alt'] }}">
                            <span class="product-zoom-lens" aria-hidden="true"></span>
                            <span class="product-zoom-hint">
                                <span class="material-symbols-outlined" aria-hidden="true">zoom_in</span>
                                Click to zoom
                            </span>
                        </button>
                        <div class="product-zoom-window" aria-hidden="true">
                            <img src="{{ $galleryImages[0]['url'] }}" alt="">
                        </div>
                        @if ($productVideos->isNotEmpty())
                            <video id="mainProductVideo" class="product-main-video" controls playsinline
                                @if ($activeProductVideo) src="{{ route('product.videos.stream', $activeProductVideo) }}"
                                @else style="display:none" @endif>
                                @if ($activeProductVideo)
                                    <source src="{{ route('product.videos.stream', $activeProductVideo) }}" type="video/mp4">
                                @endif
                            </video>
                        @endif
                        @if ($product->hasDiscount())
                            <span class="product-image-badge">{{ $discountPercentage }}% OFF</span>
                        @endif
                        <button class="product-gallery-wishlist add-to-wishlist" type="button"
                            data-product-id="{{ $product->id }}"
                            data-wishlist-key="product-{{ $product->id }}"
                            data-wishlist-url="{{ route('storefront.wishlist.toggle') }}"
                            aria-label="{{ $inWishlist ? 'Remove from wishlist' : 'Add to wishlist' }}"
                            aria-pressed="{{ $inWishlist ? 'true' : 'false' }}">
                            <span class="material-symbols-outlined" aria-hidden="true">
                                {{ $inWishlist ? 'favorite' : 'favorite_border' }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="product-information">
                    <div class="product-heading-row">
                        <div class="product-heading-copy">
                            @if ($product->material_type)
                                <p class="product-eyebrow">{{ mb_strtoupper($product->material_type) }}</p>
                            @endif
                            <h1>{{ $product->name }}</h1>
                        </div>
                        <button class="product-share-button" type="button" aria-label="Share this product">
                            <span class="material-symbols-outlined" aria-hidden="true">share</span>
                        </button>
                    </div>
                    <p class="product-share-status" role="status" aria-live="polite"></p>
                    @if ($product->short_description && ! $defaultVariation?->description)
                        <p class="product-subtitle">{{ $product->short_description }}</p>
                    @endif

                    <div class="product-rating">
                        <span class="product-stars" data-product-rating-stars
                            aria-label="{{ $reviewsCount ? 'Rated ' . number_format($averageRating, 1) . ' out of 5' : 'No ratings yet' }}">
                            {{ str_repeat('★', (int) round($averageRating)) }}{{ str_repeat('☆', 5 - (int) round($averageRating)) }}
                        </span>
                        <strong data-product-rating-average>{{ $reviewsCount ? number_format($averageRating, 1) : '—' }}</strong>
                        <a class="product-review-count" href="#product-reviews">
                            ({{ $reviewsCount }} {{ $reviewsCount === 1 ? 'review' : 'reviews' }})
                        </a>
                        @if ($currentSku)
                            <span class="rating-separator">|</span>
                            <span>SKU: <span data-product-sku>{{ $currentSku }}</span></span>
                        @endif
                    </div>

                    @if ($variations->isNotEmpty())
                        <label class="product-variation-label" for="product-variation">Choose an option</label>
                        <select class="product-variation-select" id="product-variation">
                            @foreach ($variations as $variation)
                                @php
                                    $variationPrice = $variation->effectivePrice();
                                    $variationDiscount = $variation->discountPercentage();
                                    $variationAttributes = collect($variation->attributes ?? [])
                                        ->map(fn ($value, $name) => is_scalar($value) ? $name . ': ' . $value : null)
                                        ->filter()
                                        ->values()
                                        ->join(' · ');
                                    $variationGallery = $imagesForVariation($variation);
                                    if ($variationGallery === []) {
                                        $variationGallery = [['url' => $placeholderImage, 'alt' => $product->name]];
                                    }
                                @endphp
                                <option value="{{ $variation->id }}"
                                    data-variation-id="{{ $variation->id }}"
                                    data-price="{{ $priceAttribute($variationPrice) }}"
                                    data-regular-price="{{ $priceAttribute($variation->price) }}"
                                    data-discount="{{ $discountAttribute($variationDiscount) }}"
                                    data-sku="{{ $variation->sku }}"
                                    data-stock="{{ (int) $variation->stock }}"
                                    data-description="{{ $variation->description ?: $product->description }}"
                                    data-images="{{ json_encode($variationGallery) }}"
                                    @selected($defaultVariation?->id === $variation->id)>
                                    {{ $variationAttributes ?: ($variation->sku ?: 'Option ' . $loop->iteration) }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <section class="price-and-qty">
                        <div>
                            <div class="product-price" id="product-price">
                                <strong data-product-price>₹{{ number_format($currentPrice, 0) }}</strong>
                                <del id="product-price-old" data-product-price-old
                                    @if ($discountPercentage === null) style="display:none" @endif>
                                    ₹{{ number_format($regularPrice, 0) }}
                                </del>
                                <span id="product-price-discount" class="product-discount"
                                    @if ($discountPercentage === null) style="display:none" @endif>
                                    {{ $discountPercentage !== null ? number_format($discountPercentage, 0) . '% OFF' : '' }}
                                </span>
                            </div>
                            <p class="product-tax-note">Inclusive of all taxes</p>
                        </div>
                        <div class="product-quantity">
                            <span>Quantity</span>
                            <div class="quantity-control" aria-label="Quantity">
                                <button type="button" data-quantity-change="-1" aria-label="Decrease quantity">−</button>
                                <output class="quantity-value" aria-live="polite">1</output>
                                <button type="button" data-quantity-change="1" aria-label="Increase quantity">+</button>
                            </div>
                        </div>
                    </section>

                    @if($priceHistory->isNotEmpty())
                        <section class="product-price-history" aria-labelledby="price-history-title">
                            @php
                                $latestTrackedPrice = (float) ($latestPriceHistory->sale_price ?? $latestPriceHistory->base_price);
                                $previousTrackedPrice = $previousPriceHistory ? (float) ($previousPriceHistory->sale_price ?? $previousPriceHistory->base_price) : null;
                                $chartPrices = $priceHistory->map(fn ($entry) => (float) ($entry->sale_price ?? $entry->base_price));
                                $minChart = $chartPrices->min();
                                $maxChart = $chartPrices->max();
                                $rangeChart = max(1, $maxChart - $minChart);
                                $chartPoints = $priceHistory->values()->map(fn ($entry, $index) => round($index * 100 / max(1, $priceHistory->count() - 1), 2) . ',' . round(92 - (((float) ($entry->sale_price ?? $entry->base_price) - $minChart) / $rangeChart * 78), 2));
                                if ($priceHistory->count() === 1) $chartPoints = collect(['0,53', '100,53']);
                                $chartPointsText = $chartPoints->implode(' ');
                            @endphp
                            <button type="button" class="price-history-open" data-price-history-open aria-haspopup="dialog" aria-label="View price history" title="View price history">
                                <span class="material-symbols-outlined" aria-hidden="true">monitoring</span>
                            </button>
                            <dialog class="price-history-dialog" aria-labelledby="price-history-dialog-title">
                                <div class="price-history-dialog-header"><div><h2 id="price-history-dialog-title">Complete Price History</h2><p>{{ $product->name }} · {{ $priceHistory->count() }} recorded prices, since {{ $priceHistory->first()->effective_at->format('M d, Y') }}</p></div><button type="button" aria-label="Close price history" data-price-history-close>&times;</button></div>
                                <div class="price-history-graph-wrap">
                                    <div class="price-history-y-labels"><span>₹{{ number_format($maxChart, 0) }}</span><span>₹{{ number_format(($maxChart + $minChart) / 2, 0) }}</span><span>₹{{ number_format($minChart, 0) }}</span></div>
                                    <svg class="price-history-chart" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Complete product price history graph">
                                        <line x1="0" y1="14" x2="100" y2="14" class="history-gridline"/><line x1="0" y1="53" x2="100" y2="53" class="history-gridline"/><line x1="0" y1="92" x2="100" y2="92" class="history-gridline"/>
                                        <polyline points="{{ $chartPointsText }}" fill="none" stroke="currentColor" stroke-width="2.5" vector-effect="non-scaling-stroke" />
                                        @foreach($priceHistory as $index => $entry)
                                            @php $x = $priceHistory->count() === 1 ? 50 : $index * 100 / ($priceHistory->count() - 1); $y = 92 - (((float) ($entry->sale_price ?? $entry->base_price) - $minChart) / $rangeChart * 78); @endphp
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="1.4" class="history-point"><title>{{ $entry->effective_at->format('M d, Y H:i') }} — ₹{{ number_format((float) ($entry->sale_price ?? $entry->base_price), 2) }}</title></circle>
                                        @endforeach
                                    </svg>
                                </div>
                                <div class="price-history-dates"><span>Oldest · {{ $priceHistory->first()->effective_at->format('M d, Y') }}</span><span>Newest · {{ $priceHistory->last()->effective_at->format('M d, Y') }}</span></div>
                                <div class="price-history-table-wrap"><table class="price-history-table"><thead><tr><th>Date</th><th>MRP</th><th>Sale price</th></tr></thead><tbody>
                                    @foreach($priceHistory->reverse() as $entry)
                                        <tr><td>{{ $entry->effective_at->format('M d, Y H:i') }}</td><td>₹{{ number_format((float) $entry->base_price, 2) }}</td><td>{{ $entry->sale_price !== null ? '₹' . number_format((float) $entry->sale_price, 2) : '—' }}</td></tr>
                                    @endforeach
                                </tbody></table></div>
                            </dialog>
                        </section>
                    @endif

                    <div class="product-highlights" aria-label="Product benefits">
                        <div class="product-highlight">
                            <span class="material-symbols-outlined" aria-hidden="true">verified</span>
                            <span>925 Sterling<br>Silver</span>
                        </div>
                        <div class="product-highlight">
                            <span class="material-symbols-outlined" aria-hidden="true">diamond</span>
                            <span>Premium<br>Finish</span>
                        </div>
                        <div class="product-highlight">
                            <span class="material-symbols-outlined" aria-hidden="true">local_shipping</span>
                            <span>Free<br>Delivery</span>
                        </div>
                        <div class="product-highlight">
                            <span class="material-symbols-outlined" aria-hidden="true">autorenew</span>
                            <span>
                                @if ($returnDays === null)
                                    Return<br>Support
                                @elseif ((int) $returnDays === 0)
                                    No<br>Returns
                                @else
                                    {{ $returnDays }}-Day<br>Returns
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="product-purchase-row">
                        <div class="product-stock" data-product-stock
                            data-in-stock="{{ $currentStock > 0 ? 'true' : 'false' }}">
                            <span aria-hidden="true"></span>
                            {{ $currentStock > 0 ? 'In stock — ready to ship' : 'Currently out of stock' }}
                        </div>
                    </div>

                    <div class="product-actions">
                        <button class="product-add-to-cart" type="button"
                            data-cart-add-url="{{ route('storefront.cart.add') }}"
                            data-product-id="{{ $product->id }}"
                            data-variation-id="{{ $defaultVariation?->id }}"
                            @disabled($currentStock < 1)>
                            <span class="material-symbols-outlined" aria-hidden="true">shopping_bag</span>
                            Add to Cart
                        </button>
                        <button class="product-buy-now" type="button" @disabled($currentStock < 1)>Buy Now</button>
                    </div>
                    <img class="product-jewelry-badges" src="{{ asset('frontend/images/Jewelry-Badges.png') }}"
                        alt="Jewelry quality badges" loading="lazy">
                    <p class="product-cart-status" role="status" aria-live="polite"></p>

                    @if ($product->description || $currentDescription || $returnDays !== null || $exchangeDays !== null)
                        <div class="product-accordion">
                            <details class="product-accordion-item" open>
                                <summary>Product Details</summary>
                                <div class="product-accordion-content" id="product-description" data-product-description>
                                    {!! nl2br(e($currentDescription ?: $product->description)) !!}
                                </div>
                            </details>
                            <details class="product-accordion-item">
                                <summary>Shipping Policy</summary>
                                <div class="product-accordion-content">
                                    <p>Orders are carefully packed and dispatched promptly. Delivery timelines may vary depending on your location.</p>
                                </div>
                            </details>
                            <details class="product-accordion-item">
                                <summary>Return Policy</summary>
                                <div class="product-accordion-content">
                                    @if ($returnDays === null)
                                        <p>Contact our support team to check return eligibility for this product.</p>
                                    @elseif ((int) $returnDays === 0)
                                        <p>This product is not eligible for return.</p>
                                    @else
                                        <p>Returns are available within {{ $returnDays }} {{ \Illuminate\Support\Str::plural('day', (int) $returnDays) }} after delivery.</p>
                                    @endif
                                    <p>If your order is damaged or incorrect, contact our support team for help.</p>
                                </div>
                            </details>
                            <details class="product-accordion-item">
                                <summary>Exchange Policy</summary>
                                <div class="product-accordion-content">
                                    @if ($exchangeDays === null)
                                        <p>Contact our support team to check exchange eligibility for this product.</p>
                                    @elseif ((int) $exchangeDays === 0)
                                        <p>This product is not eligible for exchange.</p>
                                    @else
                                        <p>Exchanges are available within {{ $exchangeDays }} {{ \Illuminate\Support\Str::plural('day', (int) $exchangeDays) }} after delivery.</p>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            </div>

            <dialog class="product-lightbox" aria-label="Product image gallery">
                <div class="product-lightbox-content">
                    <div class="product-lightbox-toolbar">
                        <p class="product-lightbox-counter" aria-live="polite"></p>
                        <button class="product-lightbox-close" type="button" aria-label="Close image gallery">
                            <span class="material-symbols-outlined" aria-hidden="true">close</span>
                        </button>
                    </div>
                    <div class="product-lightbox-viewer">
                        <button class="product-lightbox-nav product-lightbox-prev" type="button" aria-label="Previous image">
                            <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
                        </button>
                        <img class="product-lightbox-image" src="{{ $galleryImages[0]['url'] }}"
                            alt="{{ $galleryImages[0]['alt'] }}">
                        <button class="product-lightbox-nav product-lightbox-next" type="button" aria-label="Next image">
                            <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
                        </button>
                    </div>
                    <div class="product-lightbox-thumbnails" role="group" aria-label="Choose product image"></div>
                </div>
            </dialog>

        </section>


        </section>
    </div>

    @if ($comboOffers->isNotEmpty())
        <section class="product-combo-offers pt-6 pb-6" aria-labelledby="product-combo-offers-title">
            <div class="container">
                <div class="section-heading">
                    <h2 id="product-combo-offers-title">Combo Offers</h2>
                    <p>Save more when you shop this product in a combo.</p>
                </div>
                <div class="product-combo-offer-list">
                    @foreach ($comboOffers as $combo)
                        <article class="product-combo-offer">
                            <div class="product-combo-offer-main">
                                <div class="product-combo-offer-heading">
                                    <span class="product-combo-offer-badge"><span class="material-symbols-outlined" aria-hidden="true">redeem</span> BUNDLE DEAL</span>
                                    <span class="product-combo-offer-count">{{ $combo->sellableProducts->count() }} items</span>
                                </div>
                                <h3>{{ $combo->name }}</h3>
                                @if ($combo->description)
                                    <p class="product-combo-offer-description">{{ $combo->description }}</p>
                                @endif
                                <div class="product-combo-offer-products">
                                    @foreach ($combo->sellableProducts as $comboIndex => $comboProduct)
                                        @php
                                            $comboProductImage = $comboProduct->primary_image
                                                ?: $comboProduct->images->firstWhere('is_primary', true)?->path
                                                ?: $comboProduct->images->first()?->path;
                                        @endphp
                                        @if ($comboIndex > 0)
                                            <span class="product-combo-offer-plus" aria-hidden="true">+</span>
                                        @endif
                                        <a href="{{ url('/product/' . $comboProduct->slug) }}" class="product-combo-offer-product" title="{{ $comboProduct->name }}">
                                            <span class="product-combo-offer-product-image">
                                                @if ($comboProductImage)
                                                    <img src="{{ asset('storage/' . ltrim($comboProductImage, '/')) }}" alt="" loading="lazy">
                                                @else
                                                    <span class="material-symbols-outlined" aria-hidden="true">image</span>
                                                @endif
                                            </span>
                                            <span class="product-combo-offer-product-info"><span>{{ $comboProduct->name }}</span><strong>₹{{ number_format($comboProduct->display_price, 2) }}</strong></span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <div class="product-combo-offer-price">
                                <span class="product-combo-offer-price-label">COMBO PRICE</span>
                                <del>₹{{ number_format($combo->productsTotal(), 2) }}</del>
                                <strong>₹{{ number_format($combo->comboPrice(), 2) }}</strong>
                                <span class="product-combo-offer-saving"><span class="material-symbols-outlined" aria-hidden="true">sell</span> Save ₹{{ number_format($combo->discountAmount(), 2) }} ({{ rtrim(rtrim(number_format($combo->savingsPercent(), 1), '0'), '.') }}%)</span>
                                <form class="product-combo-buy-form" action="{{ route('storefront.cart.buy-combo') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="combo_id" value="{{ $combo->id }}">
                                    <div class="product-combo-quantity" role="group" aria-label="Combo quantity">
                                        <button type="button" data-combo-quantity-change="-1" aria-label="Decrease combo quantity">−</button>
                                        <input type="number" name="quantity" value="1" min="1" max="99" inputmode="numeric" aria-label="Combo quantity">
                                        <button type="button" data-combo-quantity-change="1" aria-label="Increase combo quantity">+</button>
                                    </div>
                                    <button class="product-combo-buy-button" type="submit"><span class="material-symbols-outlined" aria-hidden="true">shopping_bag</span> Buy Now</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($frequentlyBoughtTogether->isNotEmpty())
        <section class="handpicked pt-6 pb-6" id="frequentlyBoughtTogether" style="background-color: #fbf5eb;">
            <div class="container">
                <div class="section-heading">
                    <h2>Frequently Bought Together</h2>
                    <p>Complete your look with these popular picks.</p>
                </div>
                <div class="swiper handpickedSwiper mt-2">
                    <div class="swiper-wrapper">
                        @foreach ($frequentlyBoughtTogether as $suggestion)
                            @include('frontend.partials.best-seller-card', [
                                'products' => [[
                                    'id' => (int) $suggestion->id,
                                    'name' => $suggestion->name,
                                    'slug' => $suggestion->slug,
                                    'image' => $suggestion->primary_image
                                        ? asset('storage/' . ltrim($suggestion->primary_image, '/'))
                                        : ($suggestion->images->firstWhere('is_primary', true)
                                            ? asset('storage/' . ltrim($suggestion->images->firstWhere('is_primary', true)->path, '/'))
                                            : ($suggestion->images->first()
                                                ? asset('storage/' . ltrim($suggestion->images->first()->path, '/'))
                                                : $placeholderImage)),
                                    'hover_image' => null,
                                    'price' => $suggestion->effectivePrice(),
                                    'regular_price' => $suggestion->regularPrice(),
                                    'rating' => $suggestion->reviews_avg_rating,
                                    'reviews_count' => (int) $suggestion->reviews_count,
                                ]],
                                'wishlistProductIds' => $wishlistProductIds,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @foreach ([['products' => $upsellProducts, 'title' => 'Something More Exquisite', 'id' => 'upsellProducts'], ['products' => $crossSellProducts, 'title' => 'A Match Made in Silver', 'id' => 'crossSellProducts']] as $recommendationGroup)
        @if ($recommendationGroup['products']->isNotEmpty())
            <section class="handpicked pt-6 pb-6 product-suggestion-section" id="{{ $recommendationGroup['id'] }}">
                <div class="container">
                    <div class="section-heading">
                        <h2>{{ $recommendationGroup['title'] }}</h2>
                        <p>Discover favourites that belong together.</p>
                    </div>
                    <div class="swiper handpickedSwiper mt-2">
                        <div class="swiper-wrapper">
                            @foreach ($recommendationGroup['products'] as $suggestion)
                                @include('frontend.partials.best-seller-card', [
                                    'products' => [[
                                        'id' => (int) $suggestion->id,
                                        'name' => $suggestion->name,
                                        'slug' => $suggestion->slug,
                                        'image' => $suggestion->primary_image
                                            ? asset('storage/' . ltrim($suggestion->primary_image, '/'))
                                            : ($suggestion->images->firstWhere('is_primary', true)
                                                ? asset('storage/' . ltrim($suggestion->images->firstWhere('is_primary', true)->path, '/'))
                                                : ($suggestion->images->first()
                                                    ? asset('storage/' . ltrim($suggestion->images->first()->path, '/'))
                                                    : $placeholderImage)),
                                        'hover_image' => null,
                                        'price' => $suggestion->effectivePrice(),
                                        'regular_price' => $suggestion->regularPrice(),
                                        'rating' => null,
                                        'reviews_count' => 0,
                                    ]],
                                    'wishlistProductIds' => $wishlistProductIds,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endforeach


    @if ($relatedProducts->isNotEmpty())
        <section class="handpicked pt-6 pb-6" id="relatedProducts" style="background-color: #fbf5eb;">
            <div class="container">
                <div class="section-heading">
                    <h2>More From {{ $product->category->name }}</h2>
                    <p>Explore more pieces from this collection.</p>
                    <a href="{{ url('/products?category=' . $product->category_id) }}">View all</a>
                </div>
                <div class="swiper handpickedSwiper mt-2">
                    <div class="swiper-wrapper">
                        @foreach ($relatedProducts as $suggestion)
                            @include('frontend.partials.best-seller-card', [
                                'products' => [[
                                    'id' => (int) $suggestion->id,
                                    'name' => $suggestion->name,
                                    'slug' => $suggestion->slug,
                                    'image' => $suggestion->primary_image
                                        ? asset('storage/' . ltrim($suggestion->primary_image, '/'))
                                        : ($suggestion->images->firstWhere('is_primary', true)
                                            ? asset('storage/' . ltrim($suggestion->images->firstWhere('is_primary', true)->path, '/'))
                                            : ($suggestion->images->first()
                                                ? asset('storage/' . ltrim($suggestion->images->first()->path, '/'))
                                                : $placeholderImage)),
                                    'hover_image' => null,
                                    'price' => $suggestion->effectivePrice(),
                                    'regular_price' => $suggestion->regularPrice(),
                                    'rating' => $suggestion->reviews_avg_rating,
                                    'reviews_count' => (int) $suggestion->reviews_count,
                                ]],
                                'wishlistProductIds' => $wishlistProductIds,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="customer-reviews" id="product-reviews" data-product-id="{{ $product->id }}"
                data-server-reviews="{{ $reviewsForBrowser->toJson() }}"
                aria-labelledby="customer-reviews-title">
                <div class="reviews-heading">
                    <div>
                        <p class="product-eyebrow">YOUR EXPERIENCE MATTERS</p>
                        <h2 id="customer-reviews-title">Customer Reviews</h2>
                    </div>
                    <a class="reviews-write-link" href="#write-review">Write a Review</a>
                </div>

                <div class="reviews-overview">
                    <div class="reviews-average">
                        <strong data-reviews-average>{{ $reviewsCount ? number_format($averageRating, 1) : '—' }}</strong>
                        <span class="review-stars" data-reviews-stars
                            aria-label="{{ $reviewsCount ? 'Rated ' . number_format($averageRating, 1) . ' out of 5' : 'No ratings yet' }}">
                            {{ str_repeat('★', (int) round($averageRating)) }}{{ str_repeat('☆', 5 - (int) round($averageRating)) }}
                        </span>
                        <span data-reviews-total>
                            {{ $reviewsCount }} {{ $reviewsCount === 1 ? 'review' : 'reviews' }}
                        </span>
                    </div>
                    <div class="reviews-breakdown" aria-label="Ratings breakdown">
                        @for ($rating = 5; $rating >= 1; $rating--)
                            @php
                                $ratingCount = (int) ($ratingCounts[$rating] ?? 0);
                                $ratingWidth = $reviewsCount ? ($ratingCount / $reviewsCount) * 100 : 0;
                            @endphp
                            <div class="review-rating-row">
                                <span>{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}</span>
                                <span class="review-rating-track"><span data-rating-fill="{{ $rating }}"
                                    style="width: {{ $ratingWidth }}%"></span></span>
                                <span data-rating-count="{{ $rating }}">{{ $ratingCount }}</span>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="reviews-body">
                    <div class="reviews-list-section">
                        <h3>Recent Reviews <span data-review-list-count>({{ $reviewsCount }})</span></h3>
                        <div class="review-list" aria-live="polite">
                            @forelse ($reviews as $review)
                                <article class="review-card">
                                    <div class="review-card-header">
                                        <span class="review-card-name">{{ $review->user?->name ?: 'Customer' }}</span>
                                        <time class="review-card-date" datetime="{{ $review->created_at?->toDateString() }}">
                                            {{ $review->created_at?->format('M j, Y') }}
                                        </time>
                                    </div>
                                    <div class="review-card-stars" aria-label="Rated {{ $review->rating }} out of 5">
                                        {{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}
                                    </div>
                                    <p class="review-card-text">{{ $review->comment }}</p>
                                    @if ($review->images->isNotEmpty())
                                        <div class="review-images">
                                            @foreach ($review->images as $image)
                                                <img src="{{ asset('storage/' . ltrim($image->path, '/')) }}"
                                                    alt="Customer review photo" loading="lazy">
                                            @endforeach
                                        </div>
                                    @endif

                                </article>
                            @empty
                                <p class="reviews-empty-state">No reviews yet. Be the first to share your experience.</p>
                            @endforelse
                        </div>
                    </div>

                    <form class="review-form" id="write-review">
                        <h3>Write a Review</h3>
                        <p class="review-form-intro">Tell others what you think about this piece.</p>
                        <p class="review-storage-note">Reviews are saved in this browser.</p>
                        <fieldset class="review-rating-field">
                            <legend>Your rating <span aria-hidden="true">*</span></legend>
                            <div class="review-rating-options">
                                @for ($rating = 5; $rating >= 1; $rating--)
                                    <input type="radio" id="review-rating-{{ $rating }}" name="rating"
                                        value="{{ $rating }}" @required($rating === 5)>
                                    <label for="review-rating-{{ $rating }}" aria-label="{{ $rating }} stars"
                                        title="{{ $rating }} stars">★</label>
                                @endfor
                            </div>
                        </fieldset>
                        <label class="review-form-label" for="review-name">Your name</label>
                        <input class="review-form-input" id="review-name" name="name" type="text" maxlength="80"
                            autocomplete="name" placeholder="Enter your name" required>
                        <label class="review-form-label" for="review-text">Your review</label>
                        <textarea class="review-form-input" id="review-text" name="review" rows="4" maxlength="1000"
                            placeholder="Share your experience with this product" required></textarea>
                        <button class="review-submit-button" type="submit">Submit Review</button>
                        <p class="review-form-status" role="status" aria-live="polite"></p>
                    </form>
                </div>
            </section>

    @endsection

@push('scripts')
    <script>
        document.querySelector(".product-thumbnails")?.addEventListener("click", (event) => {
            const thumbnail = event.target.closest(".gallery-thumb-video");
            if (thumbnail) switchToVideo(thumbnail.dataset.videoUrl);
        });

        document.querySelector(".product-variation-select")?.addEventListener("change", (event) => {
            const varId = event.currentTarget.selectedOptions[0].dataset.variationId;
            renderGalleryForVariation(varId);
        });

        const priceHistoryDialog = document.querySelector(".price-history-dialog");
        document.querySelector("[data-price-history-open]")?.addEventListener("click", () => priceHistoryDialog?.showModal());
        document.querySelector("[data-price-history-close]")?.addEventListener("click", () => priceHistoryDialog?.close());
        priceHistoryDialog?.addEventListener("click", (event) => {
            if (event.target === priceHistoryDialog) priceHistoryDialog.close();
        });
        document.querySelectorAll("[data-combo-quantity-change]").forEach((button) => {
            button.addEventListener("click", () => {
                const input = button.closest(".product-combo-buy-form")?.querySelector('input[name="quantity"]');
                if (!input) return;
                const next = Number(input.value || 1) + Number(button.dataset.comboQuantityChange);
                input.value = String(Math.min(99, Math.max(1, next)));
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .product-suggestion-section,
        #relatedProducts,
        #frequentlyBoughtTogether {
            background-color: #fbf5eb !important;
        }

        .product-combo-offers{background:linear-gradient(180deg,#fff 0%,#fcfaf7 100%)}
        .product-combo-offer-list{display:grid;gap:18px;margin-top:22px}
        .product-combo-offer{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 205px;gap:24px;overflow:hidden;padding:24px;border:1px solid #e9e1d6;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgb(62 43 20 / 7%)}
        .product-combo-offer:before{position:absolute;inset:0 auto 0 0;width:4px;background:linear-gradient(180deg,#b78a4b,#6f4a20);content:""}
        .product-combo-offer-main{min-width:0}.product-combo-offer-heading{display:flex;align-items:center;gap:10px;margin-bottom:10px}.product-combo-offer-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:20px;background:#f5eee3;color:#704d22;font-size:10px;font-weight:800;letter-spacing:.7px}.product-combo-offer-badge .material-symbols-outlined{font-size:16px}.product-combo-offer-count{color:#81786d;font-size:11px}
        .product-combo-offer-main h3{margin:0;color:#30271d;font-size:20px;font-weight:700}.product-combo-offer-description{margin:5px 0 0;color:#756e65;font-size:13px;line-height:1.5}
        .product-combo-offer-products{display:flex;align-items:center;gap:12px;overflow-x:auto;margin-top:18px;padding:2px 2px 6px}
        .product-combo-offer-product{display:flex;flex:0 0 190px;align-items:center;gap:10px;min-width:0;padding:8px;border:1px solid #eee9e1;border-radius:8px;background:#fff;color:#30271d;transition:border-color .18s,box-shadow .18s}.product-combo-offer-product:hover{border-color:#c9aa7d;box-shadow:0 3px 12px rgb(62 43 20 / 9%)}
        .product-combo-offer-product-image{display:grid;flex:0 0 58px;width:58px;height:58px;place-items:center;overflow:hidden;border-radius:6px;background:#f7f4ef;color:#a09079}.product-combo-offer-product-image img{width:100%;height:100%;object-fit:cover}.product-combo-offer-product-image .material-symbols-outlined{font-size:26px}
        .product-combo-offer-product-info{display:grid;min-width:0;gap:5px}.product-combo-offer-product-info>span{overflow:hidden;font-size:11px;font-weight:600;line-height:1.35;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}.product-combo-offer-product-info strong{font-size:12px}.product-combo-offer-plus{display:grid;flex:0 0 22px;width:22px;height:22px;place-items:center;border-radius:50%;background:#f5eee3;color:#795a31;font-size:17px;font-weight:600}
        .product-combo-offer-price{display:flex;flex-direction:column;align-items:flex-end;justify-content:center;gap:7px;padding:16px;border:1px dashed #dfd1bc;border-radius:9px;background:#fcfaf7;text-align:right}.product-combo-offer-price-label{color:#81786d;font-size:10px;font-weight:800;letter-spacing:1px}.product-combo-offer-price del{color:#948b80;font-size:12px}.product-combo-offer-price strong{color:#30271d;font-size:26px;line-height:1.1}.product-combo-offer-saving{display:inline-flex;align-items:center;justify-content:center;gap:3px;padding:6px 9px;border-radius:20px;background:#eaf5ed;color:#18763c;font-size:11px;font-weight:800;white-space:nowrap}.product-combo-offer-saving .material-symbols-outlined{font-size:15px}
        .product-combo-buy-form{display:grid;gap:9px;width:100%;margin-top:5px}.product-combo-quantity{display:flex;align-items:center;justify-content:space-between;height:38px;border:1px solid #ded5c8;border-radius:5px;background:#fff}.product-combo-quantity button{width:40px;height:100%;border:0;background:transparent;color:#593d0b;font-size:20px;cursor:pointer}.product-combo-quantity input{width:48px;border:0;outline:0;background:transparent;text-align:center;font-size:14px;font-weight:700;appearance:textfield}.product-combo-quantity input::-webkit-inner-spin-button,.product-combo-quantity input::-webkit-outer-spin-button{margin:0;appearance:none}.product-combo-buy-button{display:flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:9px 14px;border:0;border-radius:5px;background:#593d0b;color:#fff;font-size:13px;font-weight:700;cursor:pointer;transition:background .18s,transform .18s}.product-combo-buy-button:hover{transform:translateY(-1px);background:#382606}.product-combo-buy-button .material-symbols-outlined{font-size:18px}
        @media(max-width:760px){.product-combo-offer{grid-template-columns:1fr;gap:16px;padding:20px}.product-combo-offer-price{display:grid;grid-template-columns:1fr auto;justify-items:start;align-items:center;text-align:left}.product-combo-offer-price-label{grid-column:1}.product-combo-offer-price del{grid-column:2;grid-row:1}.product-combo-offer-price strong{grid-column:1;grid-row:2}.product-combo-offer-saving{grid-column:2;grid-row:2}.product-combo-buy-form{grid-column:1/-1;grid-template-columns:minmax(110px,140px) minmax(0,1fr);align-items:center}}
        @media(max-width:480px){.product-combo-offer{padding:16px}.product-combo-offer-product{flex-basis:165px}.product-combo-offer-product-image{flex-basis:48px;width:48px;height:48px}.product-combo-offer-price strong{font-size:23px}}
        .product-price-history{margin:18px 20px 22px;padding:0;border:0;background:transparent;color:#30271d}
        .price-history-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
        .price-history-heading h2{margin:0;font-size:18px;font-weight:600}
        .price-history-heading p,.price-history-note{margin:5px 0 0;color:#756e65;font-size:12px;line-height:1.5}
        .price-history-open{padding:9px 12px;border:1px solid #6f4a20;border-radius:3px;background:#6f4a20;color:#fff;font:inherit;font-size:12px;font-weight:700;cursor:pointer}
        .price-history-open:hover{background:#503514}
        .price-history-stats{display:flex;flex-wrap:wrap;gap:22px;margin:16px 0 12px}
        .price-history-stats div{display:grid;gap:4px}.price-history-stats span{color:#756e65;font-size:11px}.price-history-stats strong{font-size:15px}
        .price-history-dialog{width:min(860px,calc(100vw - 28px));max-width:none;max-height:calc(100dvh - 32px);padding:24px;border:1px solid #e6ded2;border-radius:8px;background:#fff;color:#30271d;box-shadow:0 18px 70px #0004}
        .price-history-dialog::backdrop{background:#17120fb8}
        .price-history-dialog-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
        .price-history-dialog-header h2{margin:0;font-size:22px}.price-history-dialog-header p{margin:5px 0 0;color:#756e65;font-size:13px}
        .price-history-dialog-header button{width:36px;height:36px;border:1px solid #ded7cd;border-radius:50%;background:#fff;color:inherit;font-size:24px;line-height:1;cursor:pointer}
        .price-history-graph-wrap{display:grid;grid-template-columns:66px minmax(0,1fr);align-items:stretch;gap:10px}
        .price-history-y-labels{display:flex;flex-direction:column;justify-content:space-between;padding:0 0 0;text-align:right;color:#756e65;font-size:11px}
        .price-history-chart{display:block;width:100%;height:250px;overflow:visible;color:#9a6d32}
        .history-gridline{stroke:#e9e2d9;stroke-width:1;vector-effect:non-scaling-stroke}.history-point{fill:#9a6d32;stroke:#fff;stroke-width:1;vector-effect:non-scaling-stroke}
        .price-history-dates{display:flex;justify-content:space-between;margin:8px 0 18px 76px;color:#898176;font-size:10px}
        .price-history-table-wrap{max-height:240px;overflow:auto;border:1px solid #eee8df;border-radius:4px}
        .price-history-table{width:100%;border-collapse:collapse;font-size:12px}.price-history-table th,.price-history-table td{padding:10px 12px;border-bottom:1px solid #eee8df;text-align:left;white-space:nowrap}.price-history-table th{position:sticky;top:0;background:#fcfaf7;color:#756e65;font-weight:600}
        @media(max-width:480px){.product-price-history{margin-inline:12px;padding:14px}.price-history-heading{flex-direction:column}.price-history-open{width:100%}.price-history-dialog{padding:16px}.price-history-chart{height:190px}.price-history-graph-wrap{grid-template-columns:50px minmax(0,1fr)}.price-history-dates{margin-left:60px}.price-history-stats{gap:14px}}
    </style>
@endpush
