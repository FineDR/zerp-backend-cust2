<?php

require(__DIR__ . '/includes/session.php');

$Title=__('Update Pricing');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['PriceStartDate'])){$_POST['PriceStartDate'] = ConvertSQLDate($_POST['PriceStartDate']);}
if (isset($_POST['PriceEndDate'])){$_POST['PriceEndDate'] = ConvertSQLDate($_POST['PriceEndDate']);}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_add.png" title="' . __('Search') . '" alt="" />' . $Title . '</p>';

	echo '<style>
		.modern-form-container {
			max-width: 950px;
			margin: 20px auto;
			padding: 30px;
			background: var(--surface);
			border: 1px solid var(--border);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-md);
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 25px;
			margin-bottom: 30px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group.full-width {
			grid-column: 1 / -1;
		}
		.form-group label {
			font-weight: 600;
			color: var(--text-label);
			font-size: 0.9rem;
		}
		.form-group select, .form-group input {
			padding: 10px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: var(--surface);
			font-size: 0.9rem;
			transition: all var(--transition-fast);
		}
		.form-group select:focus, .form-group input:focus {
			border-color: var(--primary);
			box-shadow: 0 0 0 3px var(--primary-soft);
			outline: none;
		}
		.page_help_text {
			background: var(--primary-soft);
			color: var(--primary-hover);
			padding: 15px 20px;
			border-radius: var(--radius-md);
			border-left: 4px solid var(--primary);
			margin-bottom: 25px;
			font-size: 0.95rem;
			line-height: 1.5;
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 15px;
			border-top: 1px solid var(--border-soft);
			padding-top: 25px;
		}
		.button-group input[type="submit"] {
			padding: 12px 40px;
			border-radius: var(--radius-sm);
			font-weight: 700;
			cursor: pointer;
			border: none;
			transition: all var(--transition-fast);
			background: var(--primary);
			color: white;
		}
		.button-group input[type="submit"]:hover {
			opacity: 0.9;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px var(--primary-glow);
			background: var(--primary-hover);
		}
		.fieldhelp {
			font-size: 0.85rem;
			color: var(--text-muted);
			font-style: italic;
			margin-top: 4px;
		}
	</style>';

	echo '<div class="modern-form-container">';
	echo '<div class="page_help_text">' . __('This page adds new prices or updates already existing prices for a specified sales type (price list) and currency for the stock category selected - based on a percentage mark up from cost prices or from preferred supplier cost data or from another price list. The rounding factor ensures that prices are at least this amount or a multiple of it. A rounding factor of 5 would mean that prices would be a minimum of 5 and other prices would be expressed as multiples of 5.') . '</div>';

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="form-grid">';
	
	// Price List
	$SQL = 'SELECT sales_type, typeabbrev FROM salestypes';
	$PricesResult = DB_query($SQL);
	echo '<div class="form-group">
			<label>' . __('Price List to update') . '</label>
			<select name="PriceList">
				<option value="0">' . __('No Price List Selected') . '</option>';
	while ($PriceLists = DB_fetch_array($PricesResult)) {
		$selected = (isset($_POST['PriceList']) && $_POST['PriceList'] == $PriceLists['typeabbrev']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $PriceLists['typeabbrev'] . '">' . $PriceLists['sales_type'] . '</option>';
	}
	echo '  </select>
		  </div>';

	// Currency
	$SQL = "SELECT currency, currabrev FROM currencies";
	$Result = DB_query($SQL);
	echo '<div class="form-group">
			<label>' . __('Price List Currency') . '</label>
			<select name="CurrCode">
				<option value="0">' . __('No Currency Selected') . '</option>';
	while ($Currencies = DB_fetch_array($Result)) {
		$selected = (isset($_POST['CurrCode']) && $_POST['CurrCode'] == $Currencies['currabrev']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $Currencies['currabrev'] . '">' . $Currencies['currency'] . '</option>';
	}
	echo '  </select>
		  </div>';

	// Costing Basis
	$CostingBasis = ($_SESSION['WeightedAverageCosting'] == 1) ? __('Weighted Average Costs') : __('Standard Costs');
	echo '<div class="form-group">
			<label>' . __('Basis for Update') . '</label>
			<select name="CostType">
				<option ' . (($_POST['CostType'] == 'PreferredSupplier') ? 'selected' : '') . ' value="PreferredSupplier">' . __('Preferred Supplier Cost Data') . '</option>
				<option ' . (($_POST['CostType'] == 'StandardCost') ? 'selected' : '') . ' value="StandardCost">' . $CostingBasis . '</option>
				<option ' . (($_POST['CostType'] == 'OtherPriceList') ? 'selected' : '') . ' value="OtherPriceList">' . __('Another Price List') . '</option>
			</select>
		  </div>';

	// Base Price List (conditional)
	if (isset($_POST['CostType']) && $_POST['CostType'] == 'OtherPriceList') {
		DB_data_seek($PricesResult, 0);
		echo '<div class="form-group">
				<label>' . __('Base Price List') . '</label>
				<select name="BasePriceList">
					<option value="0">' . __('No Price List Selected') . '</option>';
		while ($PriceLists = DB_fetch_array($PricesResult)) {
			$selected = (isset($_POST['BasePriceList']) && $_POST['BasePriceList'] == $PriceLists['typeabbrev']) ? 'selected="selected"' : '';
			echo '<option ' . $selected . ' value="' . $PriceLists['typeabbrev'] . '">' . $PriceLists['sales_type'] . '</option>';
		}
		echo '  </select>
			  </div>';
	}

	// Stock Category Range
	$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categoryid";
	$Result = DB_query($SQL);
	
	echo '<div class="form-group">
			<label>' . __('Stock Category From') . '</label>
			<select name="StkCatFrom">';
	while ($MyRow = DB_fetch_array($Result)) {
		$selected = (isset($_POST['StkCatFrom']) && $MyRow['categoryid'] == $_POST['StkCatFrom']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categoryid'] . ' - ' . $MyRow['categorydescription'] . '</option>';
	}
	echo '  </select>
		  </div>';

	DB_data_seek($Result, 0);
	echo '<div class="form-group">
			<label>' . __('Stock Category To') . '</label>
			<select name="StkCatTo">';
	while ($MyRow = DB_fetch_array($Result)) {
		$selected = (isset($_POST['StkCatTo']) && $MyRow['categoryid'] == $_POST['StkCatTo']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categoryid'] . ' - ' . $MyRow['categorydescription'] . '</option>';
	}
	echo '  </select>
		  </div>';

	// Dates
	if (!isset($_POST['PriceStartDate'])) { $_POST['PriceStartDate'] = DateAdd(date($_SESSION['DefaultDateFormat']), 'd', 1); }
	if (!isset($_POST['PriceEndDate'])) { $_POST['PriceEndDate'] = DateAdd(date($_SESSION['DefaultDateFormat']), 'y', 1); }
	
	echo '<div class="form-group">
			<label>' . __('Effective From') . '</label>
			<input type="date" name="PriceStartDate" value="' . FormatDateForSQL($_POST['PriceStartDate']) . '" />
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Effective To (Blank = No End Date)') . '</label>
			<input type="date" name="PriceEndDate" value="' . FormatDateForSQL($_POST['PriceEndDate']) . '" />
		  </div>';

	// Rounding and Increase
	if (!isset($_POST['RoundingFactor'])) { $_POST['RoundingFactor'] = CurrencyTolerance($_POST['CurrCode']); }
	if (!isset($_POST['IncreasePercent'])) { $_POST['IncreasePercent'] = 0; }

	echo '<div class="form-group">
			<label>' . __('Rounding Factor') . '</label>
			<input type="text" class="number" name="RoundingFactor" value="' . $_POST['RoundingFactor'] . '" />
			<div class="fieldhelp">' . __('Example: 1 for whole numbers, 5 for multiples of 5') . '</div>
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Markup Percentage (+/-)') . '</label>
			<input type="text" name="IncreasePercent" class="number" value="' . $_POST['IncreasePercent'] . '" />
		  </div>';

	echo '</div>'; // end form-grid

	echo '<div class="button-group">
			<input type="submit" name="UpdatePrices" value="' . __('Update Prices') . '" onclick="return confirm(\'' . __('Are you sure you wish to update or add all the prices according to the criteria selected?') . '\');" />
		  </div>';

	echo '</form></div>';

echo '</form>';

if (isset($_POST['UpdatePrices'])){
	$InputError =0; //assume the best
	if ($_POST['PriceList']=='0'){
		prnMsg(__('No price list is selected to update. No updates will take place'),'error');
		$InputError =1;
	}
	if ($_POST['CurrCode']=='0'){
		prnMsg(__('No price list currency is selected to update. No updates will take place'),'error');
		$InputError =1;
	}

	if (! Is_Date($_POST['PriceEndDate']) AND $_POST['PriceEndDate']!=''){
		$InputError =1;
		prnMsg(__('The date the new price is to be in effect to must be entered in the format') . ' ' . $_SESSION['DefaultDateFormat'],'error');
	}
	if (! Is_Date($_POST['PriceStartDate'])){
		$InputError =1;
		prnMsg(__('The date this price is to take effect from must be entered in the format') . ' ' . $_SESSION['DefaultDateFormat'],'error');
	}
	if (Date1GreaterThanDate2($_POST['PriceStartDate'],$_POST['PriceEndDate']) AND $_POST['PriceEndDate']!=''){
		$InputError =1;
		prnMsg(__('The end date is expected to be after the start date, enter an end date after the start date for this price'),'error');
	}
	if (Date1GreaterThanDate2(date($_SESSION['DefaultDateFormat']),$_POST['PriceStartDate'])){
		$InputError =1;
		prnMsg(__('The date this new price is to start from is expected to be after today'),'error');
	}
	if ($_POST['StkCatTo']<$_POST['StkCatFrom']){
		prnMsg(__('The stock category from must be before the stock category to - there would be not items in the range to update'),'error');
		$InputError =1;
	}
	if ($_POST['CostType']=='OtherPriceList' AND $_POST['BasePriceList']=='0'){
		echo '<br />' . __('Base price list selected') . ': ' .$_POST['BasePriceList'];
		prnMsg(__('When you are updating prices based on another price list - the other price list must also be selected. No updates will take place until the other price list is selected'),'error');
		$InputError =1;
	}
	if ($_POST['CostType']=='OtherPriceList' AND $_POST['BasePriceList']==$_POST['PriceList']){
		prnMsg(__('When you are updating prices based on another price list - the other price list cannot be the same as the price list being used for the calculation. No updates will take place until the other price list selected is different from the price list to be updated' ),'error');
		$InputError =1;
	}

	if ($InputError==0) {
		prnMsg(__('For a log of all the prices changed this page should be printed with CTRL+P'),'info');
		echo '<br />' . __('So we are using a price list/sales type of') .' : ' . $_POST['PriceList'];
		echo '<br />' . __('updating only prices in') . ' : ' . $_POST['CurrCode'];
		echo '<br />' . __('and the stock category range from') . ' : ' . $_POST['StkCatFrom'] . ' ' . __('to') . ' ' . $_POST['StkCatTo'];
		echo '<br />' . __('and we are applying a markup percent of') . ' : ' . $_POST['IncreasePercent'];
		echo '<br />' . __('against') . ' ';

		if ($_POST['CostType']=='PreferredSupplier'){
			echo __('Preferred Supplier Cost Data');
		} elseif ($_POST['CostType']=='OtherPriceList') {
			echo __('Price List')  . ' ' . $_POST['BasePriceList'];
		} else {
			echo $CostingBasis;
		}

		if ($_POST['PriceList']=='0'){
			echo '<br />' . __('The price list/sales type to be updated must be selected first');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		if ($_POST['CurrCode']=='0'){
			echo '<br />' . __('The currency of prices to be updated must be selected first');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		if (Is_Date($_POST['PriceEndDate'])){
			$SQLEndDate = FormatDateForSQL($_POST['PriceEndDate']);
		} else {
			$SQLEndDate = '9999-12-31';
		}
		$SQL = "SELECT stockid,
						actualcost AS cost
				FROM stockmaster
				WHERE categoryid>='" . $_POST['StkCatFrom'] . "'
				AND categoryid <='" . $_POST['StkCatTo'] . "'";
		$PartsResult = DB_query($SQL);

		$IncrementPercentage = filter_number_format($_POST['IncreasePercent']/100);

		$CurrenciesResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_POST['CurrCode'] . "'");
		$CurrencyRow = DB_fetch_row($CurrenciesResult);
		$CurrencyRate = $CurrencyRow[0];

		while ($MyRow=DB_fetch_array($PartsResult)){

	//Figure out the cost to use
			if ($_POST['CostType']=='PreferredSupplier'){
				$SQL = "SELECT purchdata.price/purchdata.conversionfactor/currencies.rate AS cost
							FROM purchdata INNER JOIN suppliers
								ON purchdata.supplierno=suppliers.supplierid
								INNER JOIN currencies
								ON suppliers.currcode=currencies.currabrev
							WHERE purchdata.preferred=1 AND purchdata.stockid='" . $MyRow['stockid'] ."'";
				$ErrMsg = __('Could not get the supplier purchasing information for a preferred supplier for the item') . ' ' . $MyRow['stockid'];
				$PrefSuppResult = DB_query($SQL, $ErrMsg);
				if (DB_num_rows($PrefSuppResult)==0){
					prnMsg(__('There is no preferred supplier data for the item') . ' ' . $MyRow['stockid'] . ' ' . __('prices will not be updated for this item'),'warn');
					$Cost = 0;
				} elseif (DB_num_rows($PrefSuppResult)>1) {
					prnMsg(__('There is more than a single preferred supplier data for the item') . ' ' . $MyRow['stockid'] . ' ' . __('prices will not be updated for this item'),'warn');
					$Cost = 0;
				} else {
					$PrefSuppRow = DB_fetch_row($PrefSuppResult);
					$Cost = $PrefSuppRow[0];
				}
			} elseif ($_POST['CostType']=='OtherPriceList'){
				$SQL = "SELECT price FROM
								prices
							WHERE typeabbrev= '" . $_POST['BasePriceList'] . "'
								AND currabrev='" . $_POST['CurrCode'] . "'
								AND debtorno=''
								AND startdate <= CURRENT_DATE
								AND enddate >= CURRENT_DATE
								AND stockid='" . $MyRow['stockid'] . "'
							ORDER BY startdate DESC";
				$ErrMsg = __('Could not get the base price for the item') . ' ' . $MyRow['stockid'] . __('from the price list') . ' ' . $_POST['BasePriceList'];
				$BasePriceResult = DB_query($SQL, $ErrMsg);
				if (DB_num_rows($BasePriceResult)==0){
					prnMsg(__('There is no default price defined in the base price list for the item') . ' ' . $MyRow['stockid'] . ' ' . __('prices will not be updated for this item'),'warn');
					$Cost = 0;
				} else {
					$BasePriceRow = DB_fetch_row($BasePriceResult);
					$Cost = $BasePriceRow[0];
				}
			} else { //Must be using standard/weighted average costs
				$Cost = $MyRow['cost'];
				if ($Cost<=0){
					prnMsg(__('The cost for this item is not set up or is set up as less than or equal to zero - no price changes will be made based on zero cost items. The item concerned is:') . ' ' . $MyRow['stockid'],'warn');
				}
			}
			$_POST['RoundingFactor'] = filter_number_format($_POST['RoundingFactor']);
			if ($_POST['CostType']!='OtherPriceList'){
				$RoundedPrice = round(($Cost * (1+ $IncrementPercentage) * $CurrencyRate+($_POST['RoundingFactor']/2))/$_POST['RoundingFactor']) * $_POST['RoundingFactor'];
				if ($RoundedPrice <=0){
					$RoundedPrice = $_POST['RoundingFactor'];
				}
			} else {
				$RoundedPrice = round(($Cost * (1+ $IncrementPercentage)+($_POST['RoundingFactor']/2))/$_POST['RoundingFactor']) * $_POST['RoundingFactor'];
				if ($RoundedPrice <=0){
					$RoundedPrice = $_POST['RoundingFactor'];
				}
			}

			if ($Cost > 0) {
				$CurrentPriceResult = DB_query("SELECT price,
											 		   startdate,
													   enddate
													FROM prices
													WHERE typeabbrev= '" . $_POST['PriceList'] . "'
													AND debtorno =''
													AND currabrev='" . $_POST['CurrCode'] . "'
													AND startdate <= CURRENT_DATE
													AND enddate >= CURRENT_DATE
													AND stockid='" . $MyRow['stockid'] . "'");
				if (DB_num_rows($CurrentPriceResult)==1){
					$DayPriorToNewPrice = DateAdd($_POST['PriceStartDate'],'d',-1);
					$CurrentPriceRow = DB_fetch_array($CurrentPriceResult);
					$UpdateSQL = "UPDATE prices SET enddate='" . FormatDateForSQL($DayPriorToNewPrice) . "'
												WHERE typeabbrev='" . $_POST['PriceList'] . "'
												AND currabrev='" . $_POST['CurrCode'] . "'
												AND debtorno=''
												AND startdate ='" . $CurrentPriceRow['startdate'] . "'
												AND enddate ='" . $CurrentPriceRow['enddate'] . "'
												AND stockid='" . $MyRow['stockid'] . "'";
					$ErrMsg =__('Error updating prices for') . ' ' . $MyRow['stockid'] . ' ' . __('because');
					$Result = DB_query($UpdateSQL, $ErrMsg);

				}
				$SQL = "INSERT INTO prices (stockid,
												typeabbrev,
												currabrev,
												startdate,
												enddate,
												price)
								VALUES ('" . $MyRow['stockid'] . "',
										'" . $_POST['PriceList'] . "',
										'" . $_POST['CurrCode'] . "',
										'" . FormatDateForSQL($_POST['PriceStartDate']) . "',
										'" . $SQLEndDate . "',
								 		'" . filter_number_format($RoundedPrice) . "')";
				$ErrMsg =__('Error inserting new price for') . ' ' . $MyRow['stockid'] . ' ' . __('because');
				$Result = DB_query($SQL, $ErrMsg);
				prnMsg(__('Inserting new price for') . ' ' . $MyRow['stockid'] . ' ' . __('to') . ' ' . $RoundedPrice,'info');

			}// end if cost > 0
		}//end while loop around items in the category
	}
}
include(__DIR__ . '/includes/footer.php');
