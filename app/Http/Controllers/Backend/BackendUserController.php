<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CartSession;
use App\Models\CustomerActivityEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BackendUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $roleFilter = in_array($request->query('role'), ['all', 'user', 'admin'], true)
            ? (string) $request->query('role', 'all')
            : 'all';

        $query = User::query()
            ->with('roles')
            ->withCount('orders')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleFilter === 'admin') {
            $query->role('admin');
        } elseif ($roleFilter === 'user') {
            $query->role('user');
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => User::count(),
            'customers' => User::role('user')->count(),
            'admins' => User::role('admin')->count(),
            'with_coins' => User::where('gehna_coins', '>', 0)->count(),
        ];

        return view('backend.users.index', [
            'users' => $users,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'stats' => $stats,
        ]);
    }

    public function edit(User $user)
    {
        return view('backend.users.edit', compact('user'));
    }

    public function show(User $user): View
    {
        $user->load('roles');
        abort_if($user->hasRole('admin') || $user->can('access admin'), 404);

        $staleActiveCarts = CartSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('last_activity_at', '<', now()->subHours(24))
            ->get();
        foreach ($staleActiveCarts as $cart) {
            $cart->update(['status' => 'abandoned', 'abandoned_at' => $cart->last_activity_at]);
            CustomerActivityEvent::create([
                'user_id' => $user->id,
                'event_type' => $cart->checkout_started_at ? 'checkout_abandoned' : 'cart_abandoned',
                'cart_session_id' => $cart->id,
                'total' => $cart->total,
                'properties' => ['reason' => 'Inferred from 24 hours of cart inactivity.'],
            ]);
        }

        $cartSessions = $user->cartSessions()->with('items', 'order')->latest('started_at')->get();
        $orders = $user->orders()->with('items.product.category', 'paymentTransactions')->latest()->get();
        $events = $user->activityEvents()->with('product', 'order')->latest()->limit(500)->get();
        $completedOrders = $orders->filter(fn ($order) => $order->payment_status === 'paid' || ($order->payment_method === 'cod' && $order->status !== 'cancelled'));
        $totalSpent = $completedOrders->sum(fn ($order) => (float) $order->total);
        $favoriteCategories = $completedOrders->flatMap(fn ($order) => $order->items->pluck('product.category.name'))
            ->filter()->countBy()->sortDesc()->take(5);
        $insights = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            'total_spent' => $totalSpent,
            'average_order_value' => $completedOrders->isNotEmpty() ? $totalSpent / $completedOrders->count() : 0,
            'abandoned_carts' => $cartSessions->where('status', 'abandoned')->count(),
            'product_views' => $user->activityEvents()->where('event_type', 'product_view')->count(),
            'last_login' => $events->firstWhere('event_type', 'login')?->created_at,
        ];

        return view('backend.users.show', compact('user', 'cartSessions', 'orders', 'events', 'insights', 'favoriteCategories'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->only($user->getFillable());
        $user->update($data);
        return redirect()->route('admin.users.index')->with('success','User updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === (int) Auth::id() || $user->hasRole('admin')) {
            return back()->with('error', 'Admin accounts cannot be removed from here.');
        }

        $user->delete();

        return back()->with('success', 'User removed');
    }
}
