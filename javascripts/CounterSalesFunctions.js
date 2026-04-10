var CounterSales = {

	// itemlist is now an object: { stockid: description, ... }
	itemlist: {},
	SetItemList: function(val)
	{
		this.itemlist = val;
	},

	quickentrytableid: "",
	SetQuickEntryTableId: function(val)
	{
		this.quickentrytableid = val;
	},

	quickentryrowid: "",
	SetTotalQuickEntryRowsId: function(val)
	{
		this.quickentryrowid = val;
	},

	rowcounter: 0,
	SetRowCounter: function(val)
	{
		this.rowcounter = val;
	},

	IncreaseRowCounter: function()
	{
		this.rowcounter++;
	},

	defaultdeliverydate: "",
	SetDefaultDeliveryDate: function(val)
	{
		this.defaultdeliverydate = val;
	},

	autofillcashreceived: false,
	SetAutoFillCashReceived: function(val)
	{
		this.autofillcashreceived = val;
	},

    identifier: "",
    SetIdentifier: function(val) {
        this.identifier = val;
    },

    formId: "",
    SetFormId: function(val) {
        this.formId = val;
    },

    // AJAX Cart Actions
    _ajaxCartAction: function(data) {
        data.identifier = this.identifier;
        data.FormID = this.formId;
        return fetch('CounterSales_Ajax.php', {
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
                if (data.message) alert(data.message.replace(/<[^>]*>?/gm, ''));
                throw new Error(data.message);
            }
        });
    },

    AddItem: function(stockid, qty = 1) {
        return this._ajaxCartAction({ action: 'add', stockid: stockid, qty: qty });
    },

    RemoveItem: function(lineId) {
        // Removed confirm() for better POS flow
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
        // Removed confirm() for better POS flow
        return this._ajaxCartAction({ action: 'clear' });
    },

    _refreshCartUI: function(data) {
        const cartContainer = document.getElementById('CartItemsContainer');
        const summarySubtotal = document.getElementById('SummarySubtotal');
        const summaryTax = document.getElementById('SummaryTax');
        const taxRow = document.getElementById('TaxRow');
        const summaryGrandTotal = document.getElementById('SummaryGrandTotal');
        const totalToPay = document.getElementById('TotalAmountToPay');
        const cartTabBtn = document.getElementById('TabCart');

        if (cartContainer) {
            cartContainer.innerHTML = data.cart_html;
            cartContainer.classList.add('pulse-update');
            setTimeout(() => cartContainer.classList.remove('pulse-update'), 500);
        }
        
        // Update Subtotal (Net)
        if (summarySubtotal) {
            summarySubtotal.innerText = Number(data.cart_total).toLocaleString(undefined, {minimumFractionDigits: this.decimal});
        }

        // Update Tax Row visibility and values
        if (taxRow && summaryTax) {
            if (data.tax_total != 0) {
                taxRow.style.display = 'flex';
                summaryTax.innerText = Number(data.tax_total).toLocaleString(undefined, {minimumFractionDigits: this.decimal});
            } else {
                taxRow.style.display = 'none';
            }
        }
        
        // Update Grand Total (Label + Value)
        if (summaryGrandTotal) {
            summaryGrandTotal.innerText = data.currency + ' ' + Number(data.grand_total).toLocaleString(undefined, {minimumFractionDigits: this.decimal});
        }
        
        // Update Hidden Total for Payment Logic
        if (totalToPay) {
            totalToPay.value = data.grand_total;
            this.totaldue = data.grand_total; // Update the internal state for change calc
        }

        const hiddenTax = document.getElementById('HiddenTaxTotal');
        if (hiddenTax) {
            hiddenTax.value = data.tax_total;
        }

        this.CalculateTotals();
        
        if (cartTabBtn) {
            cartTabBtn.innerHTML = `<i class="fas fa-shopping-basket"></i> ${data.item_count}`;
        }
    },

    // Unified Search & Barcode
    HandleUnifiedSearch: function(input) {
        const term = input.value.trim();
        if (!term) {
            this._filterProductGrid("");
            return;
        }

        // Check for exact code/barcode match for instant add
        const matchedCode = this._getMatchedCode(term);
        if (matchedCode) {
            this.AddItem(matchedCode).then(() => {
                input.value = "";
                this._filterProductGrid("");
                // Audio/Visual feedback for barcode scan
                this._vibrate();
            });
        } else {
            // Filter grid results as user types
            this._filterProductGrid(term);
        }
    },

    _filterProductGrid: function(term) {
        const cards = document.querySelectorAll('.pos-product-card');
        const lowerTerm = term.toLowerCase();
        cards.forEach(card => {
            const name = card.querySelector('.pos-product-name').innerText.toLowerCase();
            const code = card.getAttribute('data-stockid').toLowerCase();
            if (name.includes(lowerTerm) || code.includes(lowerTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    },

    _vibrate: function() {
        if (navigator.vibrate) navigator.vibrate(50);
    },

	// Core logic: find the matched stock code (case-insensitive) and add to cart table
	_addItemByCode: function(code)
	{
		var codeUpper = code.toUpperCase();
		var matchedCode = this._getMatchedCode(code);

		if (matchedCode !== null) {
			var table = document.getElementById(this.quickentrytableid);
			var found = false;

			for (var j = 0, row; row = table.rows[j]; j++) {
				var col = row.cells[0];
				var input_itemcode = col ? col.firstElementChild : null;
				if (input_itemcode) {
					if (input_itemcode.value === '' || input_itemcode.value.toUpperCase() === codeUpper) {
						input_itemcode.value = matchedCode;
						var qtyInput = row.cells[1].firstElementChild;
						qtyInput.value = qtyInput.value ? parseInt(qtyInput.value) + 1 : '1';
						found = true;
						break;
					}
				}
			}

			if (!found) {
				this.AddQuickEntryRow(matchedCode);
			}
		} else {
			alert("Item code not found: " + code);
		}
	},

	// Called by the quick entry table's scan/add button (prevents default form submission)
	AddQuickEntry: function(itemcode)
	{
		event.preventDefault();
		var code = itemcode.value.trim();
		if (code !== '') {
			this._addItemByCode(code);
		}
		itemcode.value = "";
	},

	// Called by the barcode scanner input (Enter key or Add Item button)
	AddBarcodeItem: function(barcodeInput)
	{
        this.HandleUnifiedSearch(barcodeInput);
	},

	_getMatchedCode: function(code)
	{
		var codeUpper = code.toUpperCase();
		var matchedCode = null;

		if (code in this.itemlist) {
			matchedCode = code;
		} else if (codeUpper in this.itemlist) {
			matchedCode = codeUpper;
		} else {
			for (var key in this.itemlist) {
				if (key.toUpperCase() === codeUpper) {
					matchedCode = key;
					break;
				}
			}
		}

		return matchedCode;
	},

	_incrementExistingCartLine: function(code)
	{
		var quantityInput = document.querySelector('input[data-stock-id="' + code.replace(/"/g, '\\"') + '"]');

		if (!quantityInput) {
			return false;
		}

		var currentQty = parseFloat(quantityInput.value);
		if (isNaN(currentQty)) {
			currentQty = 0;
		}

		quantityInput.value = currentQty + 1;
		return true;
	},

	_submitFormButton: function(buttonId, autoFillCashReceived)
	{
		var button = document.getElementById(buttonId);
		var autoFillInput = document.getElementById("AutoFillCashReceived");

		if (autoFillInput) {
			autoFillInput.value = autoFillCashReceived ? "1" : "0";
		}

		if (button && button.form) {
			button.form.requestSubmit(button);
		}
	},

	AddQuickEntryRow: function(code)
	{
		var table = document.getElementById(this.quickentrytableid);
		var row = table.insertRow(-1);
		var cell1 = row.insertCell(0);
		var cell2 = row.insertCell(1);

		cell1.innerHTML = "<input type='text' name='part_" + this.rowcounter + "' list='ProductList' data-type='no-illegal-chars' title='Select a product from the dropdown or type a product code / name.' size='21' maxlength='20' value='" + code + "' />";
		cell2.innerHTML = "<input type='text' class='number' name='qty_" + this.rowcounter + "' size='6' maxlength='6' value='1' /><input type='hidden' name='ItemDue_" + this.rowcounter + "' value='" + this.defaultdeliverydate + "' />";

		var totalquickentry = document.getElementById(this.quickentryrowid);
		totalquickentry.value = parseInt(totalquickentry.value) + 1;

		this.IncreaseRowCounter();
	},

	totaldue: 0,
	SetTotalDue: function(val)
	{
		this.totaldue = val;
	},

	decimal: 2,
	SetDecimal: function(val)
	{
		this.decimal = val;
	},

	cashreceivedid: "",
	SetCashReceivedId: function(val)
	{
		this.cashreceivedid = val;
	},

	amountpaidid: "",
	SetAmountPaidId: function(val)
	{
		this.amountpaidid = val;
	},

	changedueid: "",
	SetChangeDueId: function(val)
	{
		this.changedueid = val;
	},



	ApplyAutoPaymentDefaults: function()
	{
		var receivedAmount = document.getElementById(this.cashreceivedid);

		if (!this.autofillcashreceived || !receivedAmount) {
			return;
		}

		receivedAmount.value = Number(this.totaldue).toFixed(this.decimal);
		this.CalculateChangeDue();
	},

	AddProductToGrid: function(stockid, index)
	{
		var qtyInput = document.getElementById('OrderQty' + index);
		if (qtyInput) {
			qtyInput.value = 1;
			qtyInput.form.submit();
		}
	},

	// AJAX Customer Search
	SearchCustomers: function(term) {
		var resultsDiv = document.getElementById('CustSearchResults');
		if (term.length < 2) {
			resultsDiv.style.display = 'none';
			return;
		}

		fetch('CustomerSearch_Ajax.php?term=' + encodeURIComponent(term))
			.then(response => response.json())
			.then(data => {
				resultsDiv.innerHTML = '';
				if (data.length > 0) {
					data.forEach(cust => {
						var div = document.createElement('div');
						div.className = 'pos-search-result-item';
						div.innerHTML = `<strong>${cust.name}</strong><br><small>${cust.id} - ${cust.address || ''}</small>`;
						div.onclick = () => this.SelectCustomer(cust.id, cust.name);
						resultsDiv.appendChild(div);
					});
					resultsDiv.style.display = 'block';
				} else {
					resultsDiv.style.display = 'none';
				}
			});
	},

	SelectCustomer: function(id, name) {
		document.querySelector('input[name="DebtorNo"]').value = id;
		// We need to submit the form to update the session
		var form = document.getElementById('SelectParts');
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'SwitchCustomer';
		input.value = id;
		form.appendChild(input);
		form.submit();
	},

	ToggleDiscount: function(lineNo) {
		var el = document.getElementById('DiscRow' + lineNo);
		el.style.display = (el.style.display === 'none') ? 'flex' : 'none';
	},

	OnPaymentMethodChange: function(select, i) {
		var bankInput = select.parentElement.querySelector('input[type="hidden"]');
		var selectedOption = select.options[select.selectedIndex];
		bankInput.value = selectedOption.getAttribute('data-bank');
	},

	CalculateTotals: function() {
		var totalToPayEl = document.getElementById('TotalAmountToPay');
        var totalToPay = totalToPayEl ? parseFloat(totalToPayEl.value) : this.totaldue;
		var totalPaid = this._calculateCurrentTotalPaid();
		var remaining = totalToPay - totalPaid;

		document.getElementById('TotalPaidDisplay').innerText = totalPaid.toFixed(this.decimal);
		document.getElementById('RemainingBalanceDisplay').innerText = Math.abs(remaining).toFixed(this.decimal);
		
		var remainingRow = document.getElementById('RemainingBalanceRow');
		if (remaining <= 0.01) {
			remainingRow.style.color = 'var(--success)';
			remainingRow.querySelector('span').innerText = 'Change/Overpaid';
		} else {
			remainingRow.style.color = 'var(--danger)';
			remainingRow.querySelector('span').innerText = 'Remaining';
		}
		this._updateChangeDue(totalPaid, totalToPay);
	},

    _updateChangeDue: function(totalPaid, totalToPay) {
        const banner = document.getElementById('ChangeDueBanner');
        const display = document.getElementById('ChangeDueDisplay');
        const hiddenChange = document.getElementById('ChangeDue');
        const hiddenCash = document.getElementById('CashReceived');
        
        const change = Math.max(0, totalPaid - totalToPay);
        
        if (change > 0.005) {
            if (banner) banner.style.display = 'flex';
            if (display) display.innerText = change.toFixed(this.decimal);
            if (hiddenChange) hiddenChange.value = change.toFixed(this.decimal);
            if (hiddenCash) hiddenCash.value = totalPaid.toFixed(this.decimal);
        } else {
            if (banner) banner.style.display = 'none';
            if (hiddenChange) hiddenChange.value = "0.00";
            if (hiddenCash) hiddenCash.value = totalPaid.toFixed(this.decimal);
        }
    },

	_calculateCurrentTotalPaid: function() {
		var total = 0;
		document.querySelectorAll('input[name^="PaymentAmounts"]').forEach(inp => {
			total += parseFloat(inp.value) || 0;
		});
		return total;
	},

    AddPaymentRow: function() {
        const container = document.getElementById('PaymentRowsContainer');
        const rows = container.querySelectorAll('.pos-payment-row');
        const rowCount = rows.length;
        const firstRow = rows[0];
        const newRow = firstRow.cloneNode(true);
        
        const i = rowCount;
        newRow.id = 'PaymentRow' + i;
        
        const select = newRow.querySelector('select');
        select.name = `PaymentMethods[${i}]`;
        select.setAttribute('onchange', `CounterSales.OnPaymentMethodChange(this, ${i})`);
        
        const bankInput = newRow.querySelector('input[type="hidden"]');
        bankInput.name = `BankAccounts[${i}]`;
        bankInput.value = select.options[select.selectedIndex].getAttribute('data-bank');

        const amountInput = newRow.querySelector('input[name^="PaymentAmounts"]');
        amountInput.name = `PaymentAmounts[${i}]`;
        amountInput.value = "0.00";
        amountInput.setAttribute('onchange', 'CounterSales.CalculateTotals()');

        // Add delete button if it's the second row or more
        let delBtn = newRow.querySelector('.delete');
        if (!delBtn) {
            delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'pos-tool-btn delete';
            delBtn.innerHTML = '<i class="fas fa-times"></i>';
            newRow.appendChild(delBtn);
        }
        delBtn.onclick = () => this.RemovePaymentRow(i);

        container.appendChild(newRow);
        this.CalculateTotals();
    },

    RemovePaymentRow: function(index) {
        const row = document.getElementById('PaymentRow' + index);
        if (row) {
            row.remove();
            this.CalculateTotals();
        }
    },

    OnSubmitSale: function(form) {
        // Ensure the ProcessSale button value is still sent when disabled
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'ProcessSale';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);

        const btn = form.querySelector('button[name="ProcessSale"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + 'Processing...';
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        }
        return true; // Allow form to submit
    },

	CalculateChangeDue: function() {
        // Deprecated - logic merged into CalculateTotals
	},

    SwitchTab: function(tab) {
        const catalog = document.getElementById('PosCatalogCol');
        const sidebar = document.getElementById('PosSidebarCol');
        const tabCatalog = document.getElementById('TabCatalog');
        const tabCart = document.getElementById('TabCart');

        if (tab === 'catalog') {
            catalog.classList.remove('mobile-hidden');
            sidebar.classList.add('mobile-hidden');
            if (tabCatalog) tabCatalog.classList.add('active');
            if (tabCart) tabCart.classList.remove('active');
        } else {
            catalog.classList.add('mobile-hidden');
            sidebar.classList.remove('mobile-hidden');
            if (tabCatalog) tabCatalog.classList.remove('active');
            if (tabCart) tabCart.classList.add('active');
        }
        window.scrollTo(0,0);
    },

    // Initialize Event Delegation for Product Clicks
    InitCatalog: function() {
        const catalog = document.getElementById('PosCatalogCol');
        if (!catalog) return;

        // Use event delegation to handle clicks on product cards
        catalog.addEventListener('click', (e) => {
            const card = e.target.closest('.pos-product-card');
            if (card) {
                const stockid = card.getAttribute('data-stockid');
                if (stockid) {
                    this.AddItem(stockid, 1);
                }
            }
        });
    }
}

// Initialize totals on load
window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('TotalAmountToPay')) {
        CounterSales.CalculateTotals();
    }
    // Initialize catalog event delegation
    CounterSales.InitCatalog();
});
