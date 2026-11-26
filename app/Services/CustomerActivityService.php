<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartSession;
use App\Models\CartSessionItem;
use App\Models\CustomerActivityEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CustomerActivityService
{
    private function trackingTablesAvailable(): bool
    {
        static $available;

        return $available ??= Schema::hasTable('cart_sessions')
            && Schema::hasTable('cart_session_items')
            && Schema::hasTable('customer_activity_events');
    }

    public function log(Request $request, string $type, array $data = []): ?CustomerActivityEvent
    {
        if (! $this->trackingTablesAvailable()) return null;
        $user = $request->user();
        if (! $user || $user->hasRole('admin') || $user->can('access admin')) return null;

        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $attributionKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
        $attribution = collect($attributionKeys)->mapWithKeys(fn ($key) => [$key => $request->query($key)])
            ->filter(fn ($value) => filled($value))->all();
        if ($request->hasSession()) {
            if ($attribution !== []) $request->session()->put('customer_marketing_attribution', $attribution);
            $attribution = $request->session()->get('customer_marketing_attribution', $attribution);
        }
        $properties = (array) ($data['properties'] ?? []);
        if ($attribution !== []) $properties['attribution'] = $attribution;
        if ($type === 'product_view' && CustomerActivityEvent::query()
            ->where('user_id', $user->id)
            ->where('event_type', 'product_view')
            ->where('product_id', $data['product_id'] ?? 0)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
            ->exists()) {
            return null;
        }
        return CustomerActivityEvent::create([
            'user_id' => $user->id,
            'event_type' => $type,
            'product_id' => $data['product_id'] ?? null,
            'cart_session_id' => $data['cart_session_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'price' => $data['price'] ?? null,
            'total' => $data['total'] ?? null,
            'properties' => $properties === [] ? null : $properties,
            'session_id' => $sessionId,
        ]);
    }

    public function activeCart(Request $request, ?User $user = null): CartSession
    {
        $user ??= $request->user();
        if (! $this->trackingTablesAvailable()) {
            throw new \RuntimeException('Customer activity tracking tables are not available.');
        }
        $sessionKey = 'customer_activity_cart_session';
        $cartId = $request->hasSession() ? $request->session()->get($sessionKey) : null;
        $cart = $cartId
            ? CartSession::query()->where('user_id', $user->id)->where('status', 'active')->find($cartId)
            : CartSession::query()->where('user_id', $user->id)->where('status', 'active')->latest('id')->first();
        if (! $cart) {
            $cart = CartSession::create(['user_id' => $user->id, 'status' => 'active', 'session_id' => $request->hasSession() ? $request->session()->getId() : null, 'started_at' => now(), 'last_activity_at' => now()]);
            if ($request->hasSession()) $request->session()->put($sessionKey, $cart->id);
        }
        $cart->forceFill(['last_activity_at' => now(), 'session_id' => $request->hasSession() ? $request->session()->getId() : $cart->session_id])->save();
        return $cart;
    }

    public function syncCart(Request $request, ?User $user = null): CartSession
    {
        $user ??= $request->user();
        $cartSession = $this->activeCart($request, $user);
        $cartRows = Cart::query()->where('user_id', $user->id)->with(['product', 'variation', 'combo'])->get();
        $activeItems = $cartSession->items()->whereNull('removed_at')->get();
        $subtotal = 0.0;

        foreach ($cartRows as $row) {
            $key = $this->lineKey($row->product_id, $row->product_variation_id, $row->combo_id);
            $product = $row->product ?? $row->variation?->product;
            $name = $row->combo?->name ?? $product?->name ?? 'Product';
            $price = (float) $row->price;
            $quantity = (int) $row->quantity;
            $subtotal += $price * $quantity;
            $item = $activeItems->first(fn (CartSessionItem $item) => $this->lineKey($item->product_id, $item->product_variation_id, $item->combo_id) === $key);
            if ($item) {
                $item->update(['quantity' => $quantity, 'unit_price' => $price, 'line_total' => $price * $quantity, 'product_name' => $name, 'sku' => $row->variation?->sku ?? $product?->sku]);
                $activeItems = $activeItems->reject(fn ($candidate) => $candidate->id === $item->id);
            } else {
                CartSessionItem::create(['cart_session_id' => $cartSession->id, 'product_id' => $row->product_id, 'product_variation_id' => $row->product_variation_id, 'combo_id' => $row->combo_id, 'product_name' => $name, 'sku' => $row->variation?->sku ?? $product?->sku, 'quantity' => $quantity, 'unit_price' => $price, 'line_total' => $price * $quantity, 'added_at' => now()]);
            }
        }

        foreach ($activeItems as $item) $item->update(['removed_at' => now()]);
        $cartSession->update(['subtotal' => $subtotal, 'total' => $subtotal, 'last_activity_at' => now()]);
        return $cartSession->load('items');
    }

    public function startCheckout(Request $request, ?float $checkoutTotal = null, ?float $discount = null): void
    {
        if (! $this->trackingTablesAvailable()) return;
        $cart = $this->syncCart($request);
        $cart->update(['total' => $checkoutTotal ?? $cart->subtotal, 'discount' => $discount ?? 0]);
        if (! $cart->checkout_started_at) {
            $cart->update(['checkout_started_at' => now()]);
            $this->log($request, 'checkout_started', ['cart_session_id' => $cart->id, 'total' => $cart->total, 'properties' => ['items' => $cart->items()->whereNull('removed_at')->count(), 'discount' => $cart->discount]]);
        }
    }

    public function recordOrderPlaced(Request $request, Order $order): void
    {
        if (! $this->trackingTablesAvailable()) return;
        $cart = $this->activeCart($request);
        $this->log($request, 'order_placed', ['cart_session_id' => $cart->id, 'order_id' => $order->id, 'total' => $order->total, 'properties' => ['order_number' => $order->order_number ?? $order->id]]);
        $cart->update(['status' => 'converted', 'converted_at' => now(), 'order_id' => $order->id, 'total' => $order->total]);
        if ($request->hasSession()) $request->session()->forget('customer_activity_cart_session');
    }

    public function confirmOrder(Order $order, ?Request $request = null): void
    {
        if (! $this->trackingTablesAvailable()) return;
        if (CustomerActivityEvent::query()->where('order_id', $order->id)->where('event_type', 'order_placed')->exists()) return;
        $createdEvent = CustomerActivityEvent::query()->where('order_id', $order->id)->where('event_type', 'order_created')->latest('id')->first();
        $cart = $createdEvent?->cartSession;
        CustomerActivityEvent::create([
            'user_id' => $order->user_id,
            'event_type' => 'order_placed',
            'cart_session_id' => $cart?->id,
            'order_id' => $order->id,
            'total' => $order->total,
            'properties' => ['order_number' => $order->order_number ?? $order->id],
            'session_id' => $request && $request->hasSession() ? $request->session()->getId() : null,
        ]);
        $cart?->update(['status' => 'converted', 'converted_at' => now(), 'order_id' => $order->id, 'total' => $order->total]);
        if ($request && $request->hasSession()) $request->session()->forget('customer_activity_cart_session');
    }

    public function logOrderCreated(Request $request, Order $order): void
    {
        if (! $this->trackingTablesAvailable()) return;
        $cart = $this->syncCart($request);
        $items = $cart->items()->whereNull('removed_at')->get()->map(fn ($item) => ['product_id' => $item->product_id, 'name' => $item->product_name, 'sku' => $item->sku, 'quantity' => $item->quantity, 'unit_price' => (float) $item->unit_price]);
        $cart->update(['total' => $order->total, 'order_id' => $order->id]);
        $this->log($request, 'order_created', ['cart_session_id' => $cart->id, 'order_id' => $order->id, 'total' => $order->total, 'properties' => ['items' => $items->values()->all()]]);
    }

    private function lineKey(?int $productId, ?int $variationId, ?int $comboId): string
    {
        return implode(':', [$productId ?? 0, $variationId ?? 0, $comboId ?? 0]);
    }
}
