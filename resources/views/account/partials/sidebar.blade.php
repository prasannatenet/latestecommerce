<nav class="account-sidebar" aria-label="Account navigation">
    <a class="{{ $activeAccountPage === 'profile' ? 'is-active' : '' }}" href="{{ route('account.profile') }}">Personal Information</a>
    <a class="{{ $activeAccountPage === 'orders' ? 'is-active' : '' }}" href="{{ route('account.orders') }}">My Orders</a>
    <a class="{{ $activeAccountPage === 'addresses' ? 'is-active' : '' }}" href="{{ route('account.addresses') }}">Manage Address</a>
    <a class="{{ $activeAccountPage === 'wishlist' ? 'is-active' : '' }}" href="{{ route('account.wishlist') }}">My Wishlist</a>
    <a href="{{ route('profile.edit') }}">Password Manager</a>
    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
</nav>
