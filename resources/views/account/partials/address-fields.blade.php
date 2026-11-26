<label>Address label<input type="text" name="label" value="{{ old('label', $address->label ?? 'Home') }}" placeholder="Home, Work" required></label>
<label>Recipient name<input type="text" name="recipient_name" value="{{ old('recipient_name', $address->recipient_name ?? $user->name) }}" required></label>
<label>Phone<input type="tel" name="phone" value="{{ old('phone', $address->phone ?? $user->phone) }}"></label>
<label class="account-form-wide">Address line 1<input type="text" name="line1" value="{{ old('line1', $address->line1 ?? '') }}" required></label>
<label class="account-form-wide">Address line 2 <span>(optional)</span><input type="text" name="line2" value="{{ old('line2', $address->line2 ?? '') }}"></label>
<label>City<input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" required></label>
<label>State<input type="text" name="state" value="{{ old('state', $address->state ?? '') }}" required></label>
<label>Postal code<input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code ?? '') }}" required></label>
<label>Country<input type="text" name="country" value="{{ old('country', $address->country ?? 'India') }}" required></label>
