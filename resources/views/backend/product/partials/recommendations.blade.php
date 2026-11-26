@php
    $oldUpsellIds = collect(old('upsell_product_ids', $upsellProducts->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
    $oldCrossSellIds = collect(old('cross_sell_product_ids', $crossSellProducts->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="df-card" data-product-recommendations data-options-url="{{ url('/admin/products/recommendation-options') }}"
     data-current-product-id="{{ $currentProductId ?? '' }}" data-current-category-id="{{ old('category_id', $product->category_id ?? '') }}"
     data-current-price="{{ old('base_price', $product->base_price ?? '') }}" data-upsell-selected="{{ json_encode($oldUpsellIds) }}"
     data-cross-sell-selected="{{ json_encode($oldCrossSellIds) }}">
    <div class="df-card-header d-flex align-items-center justify-content-between gap-3">
        <h5 class="df-card-title"><i class="bi bi-arrow-left-right"></i> Product Recommendations</h5>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted">{{ count($oldUpsellIds) }} upsells · {{ count($oldCrossSellIds) }} cross-sells selected</small>
            <button type="button" class="df-btn df-btn-secondary" data-recommendations-toggle aria-expanded="false" aria-controls="product-recommendations-fields">Show</button>
        </div>
    </div>
    <div class="df-card-body" id="product-recommendations-fields" data-recommendations-body hidden>
        <div class="mb-4" data-recommendation-group="upsell">
            <label class="df-form-label">Upsells</label>
            <p class="df-form-hint">Choose higher priced products from this product’s category.</p>
            <input type="search" class="df-form-control mb-2" placeholder="Search upsell products by name" data-product-search>
            <div class="small text-muted mb-2" data-product-results-status></div>
            <div class="d-grid gap-2" data-product-results></div>
            <div class="small fw-semibold mt-3 mb-2">Selected upsells</div>
            <div class="d-grid gap-2" data-selected-products>
                @foreach($upsellProducts->whereIn('id', $oldUpsellIds) as $recommendedProduct)
                    @include('backend.product.partials.recommendation-product', ['product' => $recommendedProduct, 'inputName' => 'upsell_product_ids[]'])
                @endforeach
            </div>
        </div>

        <hr>

        <div data-recommendation-group="cross_sell">
            <label class="df-form-label">Cross-sells</label>
            <p class="df-form-hint">Choose a category, then select products from that category.</p>
            <select name="cross_sell_category_id" class="df-form-select mb-2" data-cross-sell-category>
                <option value="">Select a category</option>
                @foreach($allCategories as $category)
                    <option value="{{ $category->id }}" @selected((string) $selectedCrossSellCategory === (string) $category->id)>{{ $category->breadcrumb }}</option>
                @endforeach
            </select>
            <input type="search" class="df-form-control mb-2" placeholder="Search cross-sell products by name" data-product-search>
            <div class="small text-muted mb-2" data-product-results-status></div>
            <div class="d-grid gap-2" data-product-results></div>
            <div class="small fw-semibold mt-3 mb-2">Selected cross-sells</div>
            <div class="d-grid gap-2" data-selected-products>
                @foreach($crossSellProducts->whereIn('id', $oldCrossSellIds) as $recommendedProduct)
                    @include('backend.product.partials.recommendation-product', ['product' => $recommendedProduct, 'inputName' => 'cross_sell_product_ids[]'])
                @endforeach
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const cards = document.querySelectorAll('[data-product-recommendations]');
                const escapeHtml = (value) => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
                const renderProduct = (product, inputName, selected = false) => `
                    <label class="d-flex align-items-center gap-2 border rounded p-2" style="cursor:pointer;">
                        <input type="checkbox" name="${inputName}" value="${escapeHtml(product.id)}" ${selected ? 'checked' : ''} data-recommendation-checkbox>
                        <img src="${escapeHtml(product.image)}" alt="" width="44" height="44" style="object-fit:cover;border-radius:6px;">
                        <span class="flex-grow-1 small">${escapeHtml(product.name)}</span>
                        <span class="small text-muted">₹${product.price}</span>
                    </label>`;

                cards.forEach((card) => {
                    const form = card.closest('form');
                    const body = card.querySelector('[data-recommendations-body]');
                    const toggle = card.querySelector('[data-recommendations-toggle]');
                    const currentCategory = form.querySelector('[name="category_id"]');
                    const currentPrice = form.querySelector('[name="base_price"]');
                    const selectedIds = {
                        upsell: JSON.parse(card.dataset.upsellSelected || '[]').map(String),
                        cross_sell: JSON.parse(card.dataset.crossSellSelected || '[]').map(String),
                    };
                    let crossSellCategoryAtLoad = card.querySelector('[data-cross-sell-category]')?.value || '';


                    const loadOptions = async (group) => {
                        const type = group.dataset.recommendationGroup;
                        const list = group.querySelector('[data-product-results]');
                        const status = group.querySelector('[data-product-results-status]');
                        const search = group.querySelector('[data-product-search]').value.trim();
                        const categoryId = type === 'upsell' ? (currentCategory?.value || card.dataset.currentCategoryId || '') : group.querySelector('[data-cross-sell-category]').value;
                        const params = new URLSearchParams({type, search});
                        if (categoryId) params.set(type === 'upsell' ? 'current_category_id' : 'category_id', categoryId);
                        if (type === 'upsell') {
                            const currentPriceValue = Number(currentPrice?.value || card.dataset.currentPrice || 0);
                            params.set('current_price', String(currentPriceValue));
                        }
                        if (card.dataset.currentProductId) params.set('product_id', card.dataset.currentProductId);

                        if (!categoryId && !search) {
                            list.innerHTML = '';
                            status.textContent = type === 'upsell' ? 'Choose a product category, then search for higher priced products.' : 'Choose a cross-sell category first.';
                            return;
                        }

                        status.textContent = 'Loading products…';
                        try {
                            const response = await fetch(`${card.dataset.optionsUrl}?${params}`, {headers: {'Accept': 'application/json'}});
                            if (!response.ok) throw new Error('Could not load products.');
                            const products = (await response.json()).data;
                            list.innerHTML = products.map((product) => renderProduct(product, type === 'upsell' ? 'upsell_product_ids[]' : 'cross_sell_product_ids[]', selectedIds[type].includes(String(product.id)))).join('');
                            products.forEach((product) => {
                                const selectedRow = group.querySelector(`[data-selected-products] [data-recommendation-selected="${product.id}"] input`);
                                const resultCheckbox = list.querySelector(`input[value="${product.id}"]`);
                                if (selectedRow && resultCheckbox) {
                                    resultCheckbox.checked = true;
                                    resultCheckbox.removeAttribute('name');
                                }
                            });
                            status.textContent = products.length ? `${products.length} product${products.length === 1 ? '' : 's'} found` : 'No matching products found.';
                        } catch (error) {
                            list.innerHTML = '';
                            status.textContent = 'Could not load products. Check the browser console for details.';
                            console.error('Recommendation products request failed:', error);
                        }
                    };

                    card.querySelectorAll('[data-recommendation-group]').forEach((group) => {
                        group.querySelector('[data-product-search]').addEventListener('input', () => loadOptions(group));
                        group.querySelector('[data-cross-sell-category]')?.addEventListener('change', (event) => {
                            if (event.target.value !== crossSellCategoryAtLoad) {
                                selectedIds.cross_sell.forEach((id) => card.querySelector(`[data-selected-products] input[name="cross_sell_product_ids[]"][value="${id}"]`)?.closest('[data-recommendation-selected]')?.remove());
                                selectedIds.cross_sell = [];
                                crossSellCategoryAtLoad = event.target.value;
                            }
                            loadOptions(group);
                        });
                    });
                    currentCategory?.addEventListener('change', () => loadOptions(card.querySelector('[data-recommendation-group="upsell"]')));
                    currentPrice?.addEventListener('input', () => loadOptions(card.querySelector('[data-recommendation-group="upsell"]')));

                    card.addEventListener('change', (event) => {
                        const checkbox = event.target.closest('[data-recommendation-checkbox]');
                        if (!checkbox) return;
                        const type = checkbox.name.startsWith('upsell_') ? 'upsell' : 'cross_sell';
                        const id = checkbox.value;
                        selectedIds[type] = checkbox.checked
                            ? [...new Set([...selectedIds[type], id])]
                            : selectedIds[type].filter((selectedId) => selectedId !== id);
                        if (checkbox.closest('[data-product-results]')) {
                            const selectedList = checkbox.closest('[data-recommendation-group]').querySelector('[data-selected-products]');
                            const existing = selectedList.querySelector(`[data-recommendation-selected="${id}"]`);
                            if (checkbox.checked && !existing) {
                                const product = {id, name: checkbox.parentElement.querySelector('span').textContent.trim(), price: checkbox.parentElement.querySelectorAll('span')[1].textContent.replace('₹', ''), image: checkbox.parentElement.querySelector('img').src};
                                selectedList.insertAdjacentHTML('beforeend', `<div data-recommendation-selected="${id}">${renderProduct(product, checkbox.name, true)}</div>`);
                                checkbox.removeAttribute('name');
                            } else if (!checkbox.checked) existing?.remove();
                        } else if (!checkbox.checked) {
                            checkbox.closest('[data-recommendation-selected]')?.remove();
                            const resultCheckbox = card.querySelector(`[data-product-results] input[value="${id}"]`);
                            if (resultCheckbox) {
                                resultCheckbox.checked = false;
                                resultCheckbox.name = checkbox.name;
                            }
                        }
                    });

                    toggle?.addEventListener('click', () => {
                        const willOpen = body.hidden;
                        body.hidden = !willOpen;
                        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                        toggle.textContent = willOpen ? 'Hide' : 'Show';

                        if (willOpen) {
                            card.querySelectorAll('[data-recommendation-group]').forEach(loadOptions);
                        }
                    });
                });
            })();
        </script>
    @endpush
@endonce
