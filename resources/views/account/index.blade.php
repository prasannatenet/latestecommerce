@extends('layouts.frontend')

@section('title', 'My Account')

@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/storefront-category.min.css') }}">
@endsection

@section('content')
    @php
        $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [];
        $firstName = old('first_name', $nameParts[0] ?? '');
        $lastName = old('last_name', $nameParts[1] ?? '');
    @endphp
    <main class="account-page account-profile-page">
        <header class="account-title-banner">
            <h1>My Account</h1>
            <p><a href="{{ route('home') }}">Home</a><span>/</span>My Account</p>
        </header>
        <div class="account-container">
            <div class="account-layout">
                @include('account.partials.sidebar', ['activeAccountPage' => 'profile'])
                <section class="account-main-panel" aria-labelledby="account-profile-title">
                    <div class="account-profile-avatar-wrap">
                        @if ($user->profile_picture)
                            <img class="account-profile-picture" src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile picture">
                        @else
                            <span class="account-profile-picture account-profile-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        @endif
                        <label class="account-avatar-upload" title="Change profile picture">
                            <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                            <input type="file" name="profile_picture" accept="image/*" form="account-profile-form" aria-label="Choose profile picture">
                        </label>
                    </div>
                    <h2 id="account-profile-title" class="visually-hidden">Personal Information</h2>
                    @if (session('success'))<p class="account-feedback" role="status">{{ session('success') }}</p>@endif
                    <form id="account-profile-form" class="account-form account-personal-form" method="post" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <label>First Name <span>*</span><input type="text" name="first_name" value="{{ $firstName }}" required autocomplete="given-name"></label>
                        <label>Last Name <span>*</span><input type="text" name="last_name" value="{{ $lastName }}" required autocomplete="family-name"></label>
                        <label class="account-form-wide">Email <span>*</span><input type="email" value="{{ $user->email }}" readonly autocomplete="email"></label>
                        <label class="account-form-wide">Phone <span>*</span><input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel"></label>
                        <label class="account-form-wide">Date of birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}" max="{{ now()->subDay()->toDateString() }}" autocomplete="bday"></label>
                        @if ($user->profile_picture)
                            <label class="account-check account-form-wide"><input type="checkbox" name="remove_profile_picture" value="1"> Remove profile picture</label>
                        @endif
                        @if ($errors->any())
                            <div class="account-errors account-form-wide" role="alert">
                                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                            </div>
                        @endif
                        <div class="account-form-actions account-form-wide"><button type="submit" class="account-button">Update Changes</button></div>
                    </form>
                </section>
            </div>
            <section class="account-benefits" aria-label="Shopping benefits">
                <div><span class="material-symbols-outlined" aria-hidden="true">local_shipping</span><p><strong>Free Shipping</strong><small>Free shipping on eligible orders</small></p></div>
                <div><span class="material-symbols-outlined" aria-hidden="true">payments</span><p><strong>Flexible Payment</strong><small>Multiple secure payment options</small></p></div>
                <div><span class="material-symbols-outlined" aria-hidden="true">headset_mic</span><p><strong>24×7 Support</strong><small>We support you every day</small></p></div>
            </section>
        </div>
    </main>
@endsection
