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
		var code = barcodeInput.value.trim();
		if (code !== '') {
			var matchedCode = this._getMatchedCode(code);

			if (matchedCode !== null) {
				if (!this._incrementExistingCartLine(matchedCode)) {
					this._addItemByCode(matchedCode);
					this._submitFormButton("AutoQuickEntrySubmit", true);
				} else {
					this._submitFormButton("AutoRecalculateSubmit", true);
				}
			} else {
				alert("Item code not found: " + code);
			}

			barcodeInput.value = "";
			barcodeInput.focus();
		}
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

	CalculateChangeDue: function()
	{
		var received_amount = document.getElementById(this.cashreceivedid);
		var paid_amount = document.getElementById(this.amountpaidid);
		var change_due = document.getElementById(this.changedueid);

		if (received_amount.value >= this.totaldue) {
			paid_amount.value = Number(this.totaldue).toFixed(this.decimal);
			change_due.value = Number(received_amount.value - this.totaldue).toFixed(this.decimal);
		} else {
			paid_amount.value = 0;
			change_due.value = 0;
		}
	},

	ApplyAutoPaymentDefaults: function()
	{
		var receivedAmount = document.getElementById(this.cashreceivedid);

		if (!this.autofillcashreceived || !receivedAmount) {
			return;
		}

		receivedAmount.value = Number(this.totaldue).toFixed(this.decimal);
		this.CalculateChangeDue();
	}
}
