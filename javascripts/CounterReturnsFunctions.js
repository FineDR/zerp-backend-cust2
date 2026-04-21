var CounterReturns = {
    identifier: "",
    formId: "",
    decimal: 2,
    totaldue: 0,

    SetIdentifier: function(val) { this.identifier = val; },
    SetFormId: function(val) { this.formId = val; },
    SetDecimal: function(val) { this.decimal = val; },

    _ajaxCartAction: function(data) {
        data.identifier = this.identifier;
        data.FormID = this.formId;
        return fetch('CounterReturns_Ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this._refreshCartUI(data);
                return data;
            } else {
                console.error("Cart Action Failed:", data.message);
                if (data.error) alert(data.error);
                throw new Error(data.message || data.error);
            }
        });
    },

    AddItem: function(stockid, qty = 1) {
        return this._ajaxCartAction({ action: 'add', stockid: stockid, qty: qty });
    },

    RemoveItem: function(lineId) {
        return this._ajaxCartAction({ action: 'remove', line_id: lineId });
    },

    UpdateQty: function(lineId, qty) {
        if (qty <= 0) return this.RemoveItem(lineId);
        return this._ajaxCartAction({ action: 'update_qty', line_id: lineId, qty: qty });
    },

    UpdateDiscount: function(lineId, discount) {
        return this._ajaxCartAction({ action: 'update_discount', line_id: lineId, discount: discount });
    },

    ClearCart: function() {
        return this._ajaxCartAction({ action: 'clear' });
    },

    _refreshCartUI: function(data) {
        const cartContainer = document.getElementById('CartItemsContainer');
        const summarySubtotal = document.getElementById('SummarySubtotal');
        const summaryTax = document.getElementById('SummaryTax');
        const summaryGrandTotal = document.getElementById('SummaryGrandTotal');
        const totalToRefund = document.getElementById('TotalAmountToRefund');

        if (cartContainer) {
            cartContainer.innerHTML = data.cart_html;
        }
        
        if (summarySubtotal) {
            summarySubtotal.innerText = Number(data.cart_total).toLocaleString(undefined, {minimumFractionDigits: this.decimal});
        }

        if (summaryTax) {
            summaryTax.innerText = Number(data.tax_total).toLocaleString(undefined, {minimumFractionDigits: this.decimal});
        }
        
        if (summaryGrandTotal) {
            summaryGrandTotal.innerText = data.currency + ' ' + Number(data.grand_total).toLocaleString(undefined, {minimumFractionDigits: this.decimal});
        }
        
        if (totalToRefund) {
            totalToRefund.value = data.grand_total;
            this.totaldue = data.grand_total;
        }

        const hiddenTax = document.getElementById('HiddenTaxTotal');
        if (hiddenTax) {
            hiddenTax.value = data.tax_total;
        }
    },

    InitSidebarSearch: function() {
        const itemSearch = document.getElementById("ItemSearch");
        const searchResults = document.getElementById("SearchResults");
        if (!itemSearch || !searchResults) return;

        itemSearch.addEventListener("input", () => {
            const term = itemSearch.value;
            if (term.length < 2) {
                searchResults.style.display = "none";
                return;
            }

            fetch("StockSearch_Ajax.php?term=" + encodeURIComponent(term))
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = "";
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement("div");
                            div.className = "db-search-result-item";
                            div.innerHTML = `<strong>${item.id}</strong> - ${item.description}`;
                            div.onclick = () => {
                                this.AddItem(item.id, 1);
                                itemSearch.value = "";
                                searchResults.style.display = "none";
                            };
                            searchResults.appendChild(div);
                        });
                        searchResults.style.display = "block";
                    } else {
                        searchResults.style.display = "none";
                    }
                });
        });

        document.addEventListener("click", (e) => {
            if (e.target !== itemSearch) {
                searchResults.style.display = "none";
            }
        });
    },

    OnSubmitReturn: function(form) {
        const btn = form.querySelector('button[name="ProcessReturn"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + 'Processing...';
        }
        return true;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    CounterReturns.InitSidebarSearch();
});
