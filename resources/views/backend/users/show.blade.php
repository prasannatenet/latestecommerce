@extends('layouts.app')

@section('content')
<div class="df-page-header">
    <div>
        <h1 class="df-page-title">Customer Activity</h1>
        <div class="text-muted">{{ $user->name }} · {{ $user->email }} · Customer #{{ $user->id }}</div>
    </div>
    <a href="{{ route('admin.users.index') }}" class="df-btn df-btn-secondary">Back to customers</a>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Orders', number_format($insights['completed_orders'])],
        ['Total spent', '₹'.number_format($insights['total_spent'], 2)],
        ['Abandoned carts', number_format($insights['abandoned_carts'])],
        ['Product views', number_format($insights['product_views'])],
    ] as [$label, $value])
        <div class="col-6 col-xl-3"><div class="df-card p-3"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div>
    @endforeach
</div>

<div class="df-card mb-4">
    <div class="df-card-header"><h5 class="df-card-title">Customer Overview</h5></div>
    <div class="df-card-body">
        <div class="row g-3">
            <div class="col-md-3"><strong>Registered</strong><div>{{ $user->created_at->format('M d, Y H:i') }}</div></div>
            <div class="col-md-3"><strong>Last login</strong><div>{{ $insights['last_login']?->format('M d, Y H:i') ?? 'No tracked login yet' }}</div></div>
            <div class="col-md-3"><strong>Average order value</strong><div>₹{{ number_format($insights['average_order_value'], 2) }}</div></div>
            <div class="col-md-3"><strong>Favorite categories</strong><div>{{ $favoriteCategories->keys()->join(', ') ?: 'Not enough purchase data' }}</div></div>
            <div class="col-md-3"><strong>Customer status</strong><div>{{ $insights['completed_orders'] > 1 ? 'Repeat buyer' : ($insights['completed_orders'] === 1 ? 'Buyer' : 'No completed purchase') }}</div>
                @if($insights['abandoned_carts'] > 0)<span class="df-badge df-badge-warning mt-1">Has abandoned cart</span>@endif
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#customer-carts" type="button">Cart History</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#customer-orders" type="button">Orders</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#customer-activity" type="button">Activity Timeline</button></li>
</ul>
<div class="tab-content">
    <section class="tab-pane fade show active" id="customer-carts">
        @forelse($cartSessions as $cart)
            <div class="df-card mb-3">
                <div class="df-card-header">
                    <h5 class="df-card-title">Cart #{{ $cart->id }} · {{ $cart->started_at->format('M d, Y H:i') }}</h5>
                    @php $statusClass = ['active' => 'df-badge-primary', 'abandoned' => 'df-badge-warning', 'converted' => 'df-badge-success'][$cart->status] ?? 'df-badge-muted'; @endphp
                    <span class="df-badge {{ $statusClass }}">{{ ucfirst($cart->status) }}</span>
                </div>
                <div class="df-card-body">
                    <div class="table-responsive"><table class="df-table"><thead><tr><th>Product snapshot</th><th>SKU</th><th>Qty</th><th>Unit price</th><th>Line total</th><th>Added / Removed</th></tr></thead><tbody>
                        @forelse($cart->items as $item)
                            <tr><td>{{ $item->product_name }}</td><td>{{ $item->sku ?: '—' }}</td><td>{{ $item->quantity }}</td><td>₹{{ number_format((float)$item->unit_price, 2) }}</td><td>₹{{ number_format((float)$item->line_total, 2) }}</td><td>{{ $item->added_at->format('M d H:i') }}@if($item->removed_at)<br><span class="text-muted">Removed {{ $item->removed_at->format('M d H:i') }}</span>@endif</td></tr>
                        @empty
                            <tr><td colspan="6">No item snapshots in this cart.</td></tr>
                        @endforelse
                    </tbody></table></div>
                    <div class="d-flex flex-wrap gap-4 mt-3"><span>Subtotal: <strong>₹{{ number_format((float)$cart->subtotal, 2) }}</strong></span><span>Total: <strong>₹{{ number_format((float)$cart->total, 2) }}</strong></span>@if($cart->checkout_started_at)<span>Checkout started: {{ $cart->checkout_started_at->format('M d, Y H:i') }}</span>@endif</div>
                </div>
            </div>
        @empty
            <div class="df-card p-4 text-muted">No tracked cart activity yet.</div>
        @endforelse
    </section>
    <section class="tab-pane fade" id="customer-orders">
        <div class="df-card"><div class="df-card-body table-responsive"><table class="df-table"><thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Order status</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr><td>#{{ $order->order_number ?? $order->id }}</td><td>{{ $order->created_at->format('M d, Y H:i') }}</td><td>@foreach($order->items as $item)<div>{{ $item->product_name }} · Qty {{ $item->quantity }} · ₹{{ number_format((float)$item->unit_price, 2) }}</div>@endforeach</td><td>₹{{ number_format((float)$order->total, 2) }}</td><td>{{ ucfirst($order->payment_status) }} ({{ strtoupper($order->payment_method) }})</td><td>{{ ucfirst($order->status) }}</td></tr>
            @empty
                <tr><td colspan="6">No orders found.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </section>
    <section class="tab-pane fade" id="customer-activity">
        <div class="df-card"><div class="df-card-body"><div class="customer-timeline">
            @forelse($events as $event)
                @php
                    $labels = ['login' => 'Logged in', 'product_view' => 'Viewed product', 'add_to_cart' => 'Added to cart', 'quantity_increased' => 'Increased quantity', 'quantity_decreased' => 'Decreased quantity', 'remove_from_cart' => 'Removed from cart', 'cart_updated' => 'Updated cart', 'checkout_started' => 'Started checkout', 'checkout_step_completed' => 'Completed checkout step', 'checkout_abandoned' => 'Checkout abandoned', 'cart_abandoned' => 'Cart abandoned', 'order_created' => 'Submitted order', 'order_placed' => 'Order placed', 'payment_success' => 'Payment successful', 'payment_failed' => 'Payment failed', 'order_cancelled' => 'Order cancelled', 'refund_completed' => 'Refund completed'];
                    $eventProduct = data_get($event->properties, 'product_name') ?: $event->product?->name;
                @endphp
                <article class="customer-timeline-item"><div class="customer-timeline-time">{{ $event->created_at->format('M d, Y · h:i A') }}</div><strong>{{ $labels[$event->event_type] ?? str($event->event_type)->replace('_', ' ')->title() }}</strong>
                    @if($eventProduct)<div>{{ $eventProduct }}@if($event->quantity) · Qty {{ $event->quantity }}@endif @if($event->price) · ₹{{ number_format((float)$event->price, 2) }}@endif</div>@endif
                    @if($event->total !== null)<div>Total: ₹{{ number_format((float)$event->total, 2) }}</div>@endif
                    @if($event->order_id)<div>Order #{{ $event->order?->order_number ?? $event->order_id }}</div>@endif
                    @if(data_get($event->properties, 'previous_quantity') !== null)<div>Qty {{ data_get($event->properties, 'previous_quantity') }} → {{ data_get($event->properties, 'new_quantity') }}</div>@endif
                </article>
            @empty
                <p class="text-muted mb-0">No tracked activity yet. Tracking starts after the feature is deployed.</p>
            @endforelse
        </div></div></div>
    </section>
</div>

<style>
    .customer-timeline{border-left:2px solid #dce5e5;margin-left:8px;padding-left:22px}.customer-timeline-item{position:relative;padding:0 0 22px}.customer-timeline-item:before{content:"";position:absolute;left:-29px;top:5px;width:11px;height:11px;border:2px solid #017075;border-radius:50%;background:#fff}.customer-timeline-time{color:#6c757d;font-size:.8rem;margin-bottom:3px}
</style>
@endsection
