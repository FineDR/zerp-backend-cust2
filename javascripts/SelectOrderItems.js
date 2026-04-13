document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('GlobalSearch');
    const partsForm = document.getElementById('SelectParts');
    const productGrid = document.querySelector('.db-product-grid');
    const sidebarCart = document.querySelector('.db-pos-sidebar');
    const identifier = getIdentifier();

    function getIdentifier() {
        const identifierInput = document.querySelector('input[name="identifier"]');
        return identifierInput ? identifierInput.value : new URLSearchParams(window.location.search).get('identifier');
    }

    // Helper to format currency
    function formatCurrency(amount, currency = '', decimals = 2) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(amount) + (currency ? ' ' + currency : '');
    }

    // Render results into product grid
    function renderSearchResults(products) {
        if (!productGrid) return;
        
        if (products.length === 0) {
            productGrid.innerHTML = `
                <div class="db-card" style="grid-column: 1/-1; text-align: center; padding: var(--space-8); opacity: 0.6;">
                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: var(--space-4);"></i>
                    <h3>No products found</h3>
                    <p>Try different keywords or category.</p>
                </div>`;
            return;
        }

        productGrid.innerHTML = products.map((p, j) => `
            <div class="db-product-card">
                <div class="db-product-image" title="${p.LongDescription}">
                    <i class="fas fa-box"></i>
                    ${p.QOH <= 0 ? '<span class="db-badge danger">Out of Stock</span>' : '<span class="db-badge success" style="background: var(--primary); color: white; border:none;">In Stock</span>'}
                </div>
                <div class="db-product-content">
                    <span class="db-product-id">${p.StockID}</span>
                    <h4 class="db-product-name">${p.Description}</h4>
                    <div class="db-product-price">
                        ${p.DisplayPrice} <small>${getCurrency()}</small>
                    </div>
                    <div class="db-product-meta">
                        <span><i class="fas fa-layer-group"></i> ${p.DisplayQOH} ${p.Units}</span>
                        <span><i class="fas fa-check-circle"></i> Avail: ${p.DisplayQOH}</span>
                    </div>
                </div>
                <div class="db-product-card-footer">
                    <input class="db-input db-input-sm number" type="text" name="OrderQty${j}" value="0" placeholder="Qty" />
                    <input name="StockID${j}" type="hidden" value="${p.StockID}" />
                    <button type="button" class="db-btn db-btn-primary db-btn-sm js-add-to-cart" style="padding: 0 var(--space-4); height: 32px;">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>
        `).join('');
    }

    function getCurrency() {
        const currencySpan = document.querySelector('.db-sidebar-total-main span:last-child');
        return currencySpan ? currencySpan.textContent.split(' ').pop() : '';
    }

    // Render sidebar cart
    function renderSidebarCart(cart) {
        if (!sidebarCart) return;

        if (cart.status === 'inactive') {
            sidebarCart.innerHTML = `
                <div class="db-card" style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: var(--text-muted); padding: var(--space-8); min-height: 400px;">
                    <i class="fas fa-user-plus" style="font-size: 3rem; margin-bottom: var(--space-4); opacity: 0.3;"></i>
                    <h3>Cart Inactive</h3>
                    <p>Please select a customer branch to start adding products.</p>
                </div>`;
            return;
        }

        if (!cart.Items || cart.Items.length === 0) {
            sidebarCart.innerHTML = `
                <div class="db-card" style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: var(--space-8); min-height: 400px;">
                    <div class="db-sidebar-cart-header" style="width: 100%; margin-bottom: auto;">
                        <h3 class="db-card-title"><i class="fas fa-shopping-cart"></i> Your Order</h3>
                    </div>
                    <i class="fas fa-shopping-basket" style="font-size: 3rem; margin-bottom: var(--space-4); color: var(--primary-soft); opacity: 0.5;"></i>
                    <h3>Your cart is empty</h3>
                    <p style="color: var(--text-muted);">Search products on the left and add them to your order.</p>
                    <div style="margin-top: auto; width: 100%; padding-top: var(--space-6); border-top: 1px solid var(--border-soft);">
                        <p class="db-muted" style="font-size: 0.8rem;">Customer: <b>${cart.CustomerName}</b></p>
                    </div>
                </div>`;
            return;
        }

        let itemsHtml = cart.Items.map(item => `
            <div class="db-sidebar-item" data-line="${item.LineNumber}">
                <div class="db-sidebar-item-row">
                    <div class="db-sidebar-item-name">${item.ItemDescription}</div>
                    <button type="button" class="db-btn-icon db-btn-sm db-btn-danger js-remove-item" style="width:20px; height:20px; font-size:10px;">
                        <i class="fas ${item.Invoiced > 0 ? 'fa-eraser' : 'fa-times'}"></i>
                    </button>
                </div>
                <div class="db-sidebar-item-meta">${item.StockID}</div>
                <div class="db-sidebar-item-actions">
                    <div class="db-sidebar-item-qty">
                        <input type="text" class="db-input db-input-sm number js-update-qty" value="${item.Quantity}" style="width:50px;" />
                        <span class="db-muted">${item.Units}</span>
                    </div>
                    <div class="db-sidebar-item-price">
                        <b>${item.DisplayLineTotal}</b>
                    </div>
                </div>
            </div>
        `).join('');

        sidebarCart.innerHTML = `
            <div class="db-sidebar-cart">
                <div class="db-sidebar-cart-header">
                    <h3 class="db-card-title"><i class="fas fa-shopping-cart"></i> Your Order</h3>
                    <span class="db-badge">${cart.ItemsOrdered} Items</span>
                </div>
                <div class="db-sidebar-cart-body">
                    ${itemsHtml}
                </div>
                <div class="db-sidebar-cart-footer">
                    <div class="db-sidebar-total-row">
                        <span>Subtotal</span>
                        <span>${cart.DisplaySubtotal}</span>
                    </div>
                    <div class="db-sidebar-total-main">
                        <span>Total</span>
                        <span>${cart.DisplaySubtotal} ${cart.Currency}</span>
                    </div>
                    <div class="db-actions" style="margin-top: var(--space-6); display: flex; flex-direction: column; gap: 12px;">
                        <input type="submit" form="SelectParts" name="DeliveryDetails" class="db-btn db-btn-primary" style="width: 100%;" value="Proceed to Final Review" />
                    </div>
                </div>
            </div>`;
    }

    // Skeleton loading state
    function showSearchLoading() {
        if (!productGrid) return;
        productGrid.innerHTML = Array(4).fill(0).map(() => `
            <div class="db-product-card loading">
                <div class="db-product-image shimmer"></div>
                <div class="db-product-content">
                    <div class="shimmer" style="height: 12px; width: 40%; margin-bottom: 8px;"></div>
                    <div class="shimmer" style="height: 20px; width: 80%; margin-bottom: 8px;"></div>
                    <div class="shimmer" style="height: 24px; width: 30%; margin-bottom: 15px;"></div>
                    <div class="shimmer" style="height: 12px; width: 60%;"></div>
                </div>
            </div>
        `).join('');
    }

    // Fetch and search
    function performSearch() {
        const keywords = searchInput.value;
        const stockCat = document.querySelector('select[name="StockCat"]').value;
        
        showSearchLoading();

        fetch(`SelectOrderItems.php?Ajax=SearchProducts&Keywords=${encodeURIComponent(keywords)}&StockCat=${stockCat}&identifier=${identifier}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderSearchResults(data.products);
                }
            });
    }

    // Add to Cart
    document.addEventListener('click', function(e) {
        const addBtn = e.target.closest('.js-add-to-cart');
        if (addBtn) {
            const card = addBtn.closest('.db-product-card');
            const stockID = card.querySelector('input[name^="StockID"]').value;
            const qty = card.querySelector('input[name^="OrderQty"]').value;

            if (parseFloat(qty) <= 0) {
                alert('Please enter a quantity greater than zero.');
                return;
            }

            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`SelectOrderItems.php?Ajax=AddToCart&StockID=${encodeURIComponent(stockID)}&Qty=${qty}&identifier=${identifier}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderSidebarCart(data.cart);
                        addBtn.disabled = false;
                        addBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
                        // Reset qty input
                        card.querySelector('input[name^="OrderQty"]').value = '0';
                    }
                });
        }

        const removeBtn = e.target.closest('.js-remove-item');
        if (removeBtn) {
            const itemRow = removeBtn.closest('.db-sidebar-item');
            const line = itemRow.dataset.line;

            removeBtn.disabled = true;
            removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`SelectOrderItems.php?Ajax=RemoveItem&LineNumber=${line}&identifier=${identifier}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderSidebarCart(data.cart);
                    }
                });
        }
    });

    // Update Quantity in Sidebar (Debounced)
    let updateTimer;
    document.addEventListener('input', function(e) {
        const qtyInput = e.target.closest('.js-update-qty');
        if (qtyInput) {
            const itemRow = qtyInput.closest('.db-sidebar-item');
            const line = itemRow.dataset.line;
            const qty = qtyInput.value;

            clearTimeout(updateTimer);
            updateTimer = setTimeout(() => {
                fetch(`SelectOrderItems.php?Ajax=UpdateQty&LineNumber=${line}&Qty=${qty}&identifier=${identifier}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            renderSidebarCart(data.cart);
                        }
                    });
            }, 500);
        }
    });

    // Live Search
    let debounceTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(performSearch, 500);
        });
    }

    // Refresh Category Search
    const catSelect = document.querySelector('select[name="StockCat"]');
    if (catSelect) {
        catSelect.addEventListener('change', performSearch);
    }
    
    // Initial Cart Load
    if (sidebarCart && identifier) {
        fetch(`SelectOrderItems.php?Ajax=GetCart&identifier=${identifier}`)
            .then(res => res.json())
            .then(cart => {
                renderSidebarCart(cart);
            });
    }
});
