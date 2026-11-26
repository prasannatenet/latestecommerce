<div class="df-card">
    <div class="df-card-header">
        <h5 class="df-card-title"><i class="bi bi-arrow-repeat"></i> Return &amp; Exchange Window</h5>
    </div>
    <div class="df-card-body">
        <p class="df-form-hint">Set product-specific windows after delivery. Leave blank to use the Store Setup default.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="return_days" class="df-form-label">Return days</label>
                <input id="return_days" type="number" name="return_days" class="df-form-control" min="0" max="3650" step="1" value="{{ old('return_days', $product?->return_days) }}" placeholder="Use store default">
                <small class="text-muted">Days after delivery; enter 0 to disable returns for this product.</small>
            </div>
            <div class="col-md-6">
                <label for="exchange_days" class="df-form-label">Exchange days</label>
                <input id="exchange_days" type="number" name="exchange_days" class="df-form-control" min="0" max="3650" step="1" value="{{ old('exchange_days', $product?->exchange_days) }}" placeholder="Use store default">
                <small class="text-muted">Days after delivery; enter 0 to disable exchanges for this product.</small>
            </div>
        </div>
    </div>
</div>
