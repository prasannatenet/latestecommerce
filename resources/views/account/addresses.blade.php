@extends('layouts.frontend')

@section('title', 'Manage Addresses')

@section('content')
    <main class="account-page account-secondary-page">
        <header class="account-title-banner"><h1>Manage Address</h1><p><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('account.profile') }}">My Account</a><span>/</span>Manage Address</p></header>
        <div class="account-container account-layout">
            @include('account.partials.sidebar', ['activeAccountPage' => 'addresses'])
            <section class="account-main-panel account-address-panel" aria-labelledby="account-address-title">
                <div class="account-panel-heading"><div><p class="account-eyebrow">DELIVERY DETAILS</p><h2 id="account-address-title">My Addresses</h2></div><span>{{ $addresses->count() }} / 5 saved</span></div>
                @if (session('success'))<p class="account-feedback" role="status">{{ session('success') }}</p>@endif
                @error('address_limit')<p class="account-error" role="alert">{{ $message }}</p>@enderror
                @if ($addresses->isEmpty())<p class="account-address-empty">Add your first complete delivery address. It will become your primary address.</p>@endif
                <div class="account-address-list">
                    @foreach ($addresses as $address)
                        <details class="account-address-card" {{ $errors->any() && old('address_id') == $address->id ? 'open' : '' }}>
                            <summary><span><strong>{{ $address->label }}</strong> · {{ $address->recipient_name }} @if ($address->is_primary)<em>Primary</em>@endif</span><span>{{ $address->city }}, {{ $address->state }}</span></summary>
                            <div class="account-address-body">
                                <p>{{ $address->line1 }}@if($address->line2), {{ $address->line2 }}@endif<br>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>{{ $address->country }}@if($address->phone)<br>{{ $address->phone }}@endif</p>
                                <form class="account-form" method="post" action="{{ route('account.addresses.update', $address) }}">
                                    @csrf @method('put')<input type="hidden" name="address_id" value="{{ $address->id }}">
                                    @include('account.partials.address-fields', ['address' => $address, 'prefix' => 'address-'.$address->id])
                                    <label class="account-check account-form-wide"><input type="checkbox" name="is_primary" value="1" {{ $address->is_primary ? 'checked' : '' }}> Set as primary address</label>
                                    <div class="account-form-actions account-form-wide"><button class="account-button" type="submit">Save address</button></div>
                                </form>
                                <div class="account-address-actions">
                                    @unless($address->is_primary)<form method="post" action="{{ route('account.addresses.primary', $address) }}">@csrf @method('patch')<button type="submit" class="account-text-button">Make primary</button></form>@endunless
                                    <form method="post" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this saved address?')">@csrf @method('delete')<button type="submit" class="account-text-button account-danger-button">Remove</button></form>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
                @if ($addresses->count() < 5)
                    <details class="account-add-address" {{ old('label') && ! old('address_id') ? 'open' : '' }}>
                        <summary>Add another address</summary>
                        <form class="account-form" method="post" action="{{ route('account.addresses.store') }}">
                            @csrf
                            @include('account.partials.address-fields', ['address' => null, 'prefix' => 'new-address'])
                            <label class="account-check account-form-wide"><input type="checkbox" name="is_primary" value="1"> Set as primary address</label>
                            <div class="account-form-actions account-form-wide"><button class="account-button" type="submit">Save address</button></div>
                        </form>
                    </details>
                @endif
            </section>
        </div>
    </main>
@endsection
