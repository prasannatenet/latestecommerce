<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FrontendAccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('account.index', compact('user'));
    }

    public function addresses(Request $request): View
    {
        $user = $request->user();
        $addresses = $user->addresses()->orderByDesc('is_primary')->orderBy('id')->get();

        return view('account.addresses', compact('user', 'addresses'));
    }

    public function orders(Request $request): View
    {
        $user = $request->user();
        $orders = $user->orders()->with('items')->latest()->paginate(10);

        return view('account.orders', compact('user', 'orders'));
    }

    public function wishlist(Request $request): View
    {
        $user = $request->user();
        $wishlistItems = $user->wishlist()
            ->with(['product.images'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get()
            ->unique('product_id')
            ->values();

        return view('account.wishlist', compact('user', 'wishlistItems'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:1', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'profile_picture' => ['nullable', 'image', 'max:5120'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ]);

        unset($data['remove_profile_picture']);
        $data['name'] = trim($data['first_name'].' '.$data['last_name']);
        unset($data['first_name'], $data['last_name']);
        if ($request->boolean('remove_profile_picture') && $user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $data['profile_picture'] = null;
        }
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
        }

        $user->fill($data)->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->addresses()->count() >= 5) {
            return back()->withErrors(['address_limit' => 'You can save up to five addresses.'])->withInput();
        }

        $data = $this->validatedAddress($request);
        $makePrimary = $request->boolean('is_primary') || ! $user->addresses()->exists();
        if ($makePrimary) {
            $user->addresses()->update(['is_primary' => false]);
        }
        $data['is_primary'] = $makePrimary;
        $user->addresses()->create($data);

        return back()->with('success', 'Address saved successfully.');
    }

    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $data = $this->validatedAddress($request);
        $makePrimary = $request->boolean('is_primary');
        if ($makePrimary) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_primary' => false]);
        } elseif ($address->is_primary) {
            $makePrimary = true;
        }
        $data['is_primary'] = $makePrimary;
        $address->update($data);

        return back()->with('success', 'Address updated successfully.');
    }

    public function deleteAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $wasPrimary = $address->is_primary;
        $address->delete();
        if ($wasPrimary) {
            $request->user()->addresses()->oldest('id')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Address removed.');
    }

    public function setPrimaryAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $request->user()->addresses()->whereKeyNot($address->id)->update(['is_primary' => false]);
        $address->update(['is_primary' => true]);

        return back()->with('success', 'Primary address updated.');
    }

    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ]);
    }
}
