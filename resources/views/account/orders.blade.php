@extends('layouts.frontend')

@section('title', 'Order History')

@section('content')
    <main class="account-page account-secondary-page">
        <header class="account-title-banner"><h1>My Orders</h1><p><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('account.profile') }}">My Account</a><span>/</span>My Orders</p></header>
        <div class="account-container account-layout">
            @include('account.partials.sidebar', ['activeAccountPage' => 'orders'])
            <section class="account-main-panel account-orders-panel" aria-label="Your orders">
                <div class="account-panel-heading"><div><p class="account-eyebrow">YOUR PURCHASES</p><h2>Order History</h2></div><span>{{ $orders->total() }} {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}</span></div>
                @forelse ($orders as $order)
                    <article class="account-order">
                        <header class="account-order-header"><div><h3>Order #{{ $order->id }}</h3><p>Placed {{ $order->created_at->format('M j, Y') }}</p></div><span class="account-order-status">{{ ucfirst($order->status) }}</span></header>
                        <ul class="account-order-items">
                            @foreach ($order->items as $item)
                                <li><span>{{ $item->product_name }} <span class="account-item-quantity">&times; {{ $item->quantity }}</span></span><span>&#8377;{{ number_format((float) $item->line_total, 2) }}</span></li>
                            @endforeach
                        </ul>
                        <footer class="account-order-total"><span>Order total</span><strong>&#8377;{{ number_format((float) $order->total, 2) }}</strong></footer>
                    </article>
                @empty
                    <div class="account-orders-empty"><h3>No orders yet</h3><p>Your orders will appear here after your first purchase.</p><a href="{{ route('storefront.products') }}">Explore jewellery</a></div>
                @endforelse
                @if ($orders->hasPages())
                    @include('account.partials.pagination', ['paginator' => $orders])
                @endif
            </section>
        </div>
    </main>
@endsection
