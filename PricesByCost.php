<?php

require(__DIR__ . '/includes/session.php');

if (isset($_POST['submit']) OR isset($_POST['update']) && (@$_POST['Margin'] == '')) {
	header('Location: ' . htmlspecialchars_decode($RootPath) . '/PricesByCost.php');
	exit();
}

$Title = __('Update of Prices By A Multiple Of Cost');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR: Filters
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-filter"></i> ' . __('Pricing Filters') . '</h3>
		</div>
		<div class="db-card-body">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

// Category
$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$Result1 = DB_query($SQL);
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Stock Category') . '</label>
					<select name="StockCat" class="db-select">
						<option value="all">' . __('All Categories') . '</option>';
while ($MyRow1 = DB_fetch_array($Result1)) {
	echo '				<option ' . ((isset($_POST['StockCat']) && $_POST['StockCat'] == $MyRow1['categoryid']) ? 'selected' : '') . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
}
echo '				</select>
				</div>';

// Comparator & Margin
if (!isset($_POST['Margin'])) {
	$_POST['Margin'] = 1;
}
$CostType = ($_SESSION['WeightedAverageCosting'] == 1) ? __('Average Cost') : __('Standard Cost');

echo '			<div class="db-form-group">
					<label class="db-label">' . __('Price Comparison') . '</label>
					<div style="display: flex; gap: 8px;">
						<select name="Comparator" class="db-select" style="flex: 2;">
							<option ' . ((isset($_POST['Comparator']) && $_POST['Comparator'] == 1) ? 'selected' : '') . ' value="1">' . __('<=') . '</option>
							<option ' . ((isset($_POST['Comparator']) && $_POST['Comparator'] == 2) ? 'selected' : '') . ' value="2">' . __('>=') . '</option>
						</select>
						<div style="flex: 3; display: flex; align-items: center; gap: 5px;">
							<span class="db-muted" style="font-size: 0.8rem;">' . $CostType . ' x</span>
							<input type="text" class="db-input text-right" name="Margin" maxlength="8" size="5" value="' . $_POST['Margin'] . '" />
						</div>
					</div>
				</div>';

// Sales Type
$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes");
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Price List (Sales Type)') . '</label>
					<select name="SalesType" class="db-select">';
while ($MyRow = DB_fetch_array($Result)) {
	echo '				<option ' . ((isset($_POST['SalesType']) && $_POST['SalesType'] == $MyRow['typeabbrev']) ? 'selected' : '') . ' value="' . $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';
}
echo '				</select>
				</div>';

// Currency
$Result = DB_query("SELECT currency, currabrev FROM currencies");
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Currency') . '</label>
					<select name="CurrCode" class="db-select">';
while ($MyRow = DB_fetch_array($Result)) {
	echo '				<option ' . ((isset($_POST['CurrCode']) && $_POST['CurrCode'] == $MyRow['currabrev']) ? 'selected' : '') . ' value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
}
echo '				</select>
				</div>';

echo '			<button type="submit" name="submit" value="' . __('Submit') . '" class="db-btn db-btn-primary w-100">
					<i class="fas fa-search"></i> ' . __('Search Items') . '
				</button>
			</form>
		</div>
	  </div>
	</aside>';

// MAIN CONTENT
echo '<main class="db-col-main">';

if (isset($_POST['submit']) OR isset($_POST['update'])) {
	if ($_POST['Comparator'] == 1) {
		$Comparator = '<=';
	} else {
		$Comparator = '>=';
	} /*end of else Comparator */
	if ($_POST['StockCat'] != 'all') {
		$Category = " AND stockmaster.categoryid = '" . $_POST['StockCat'] . "'";
	} else {
		$Category ='';
	}/*end of else StockCat */

	$SQL = "SELECT 	stockmaster.stockid,
					stockmaster.description,
					prices.debtorno,
					prices.branchcode,
					(stockmaster.actualcost) as cost,
					prices.price as price,
					prices.debtorno AS customer,
					prices.branchcode AS branch,
					prices.startdate,
					prices.enddate,
					currencies.decimalplaces,
					currencies.rate
				FROM stockmaster INNER JOIN prices
				ON stockmaster.stockid=prices.stockid
				INNER JOIN currencies
				ON prices.currabrev=currencies.currabrev
				WHERE stockmaster.discontinued = 0
					" . $Category . "
					AND   prices.price" . $Comparator . "(stockmaster.actualcost) * '" . filter_number_format($_POST['Margin']) . "'
					AND prices.typeabbrev ='" . $_POST['SalesType'] . "'
					AND prices.currabrev ='" . $_POST['CurrCode'] . "'
					AND prices.enddate >= CURRENT_DATE";
	$Result = DB_query($SQL);
	$NumRow = DB_num_rows($Result);

	if ($_POST['submit'] == 'Update') {
			//Update Prices
		$PriceCounter =0;
		while ($MyRow = DB_fetch_array($Result)) {
			/*The logic here goes like this:
			 * 1. If the price at the same start and end date already exists then do nowt!!
			 * 2. If not then check if a price with the start date of today already exists - then we should be updating it
			 * 3. If not either of the above then insert the new price
			*/
			$SQLTestExists = "SELECT price FROM prices
								WHERE stockid = '" . $_POST['StockID_' . $PriceCounter] . "'
								AND prices.typeabbrev ='" . $_POST['SalesType'] . "'
								AND prices.currabrev ='" . $_POST['CurrCode'] . "'
								AND prices.debtorno ='" . $_POST['DebtorNo_' . $PriceCounter] . "'
								AND prices.branchcode ='" . $_POST['BranchCode_' . $PriceCounter] . "'
								AND prices.startdate ='" . $_POST['StartDate_' . $PriceCounter] . "'
								AND prices.enddate ='" . $_POST['EndDate_' . $PriceCounter] . "'
								AND prices.price ='" . filter_number_format($_POST['Price_' . $PriceCounter]) . "'";
			$TestExistsResult = DB_query($SQLTestExists);
			if (DB_num_rows($TestExistsResult)==0){ //the price doesn't currently exist
				//now check to see if a new price has already been created from start date of today

				$SQLTestExists = "SELECT price FROM prices
									WHERE stockid = '" . $_POST['StockID_' . $PriceCounter] . "'
										AND prices.typeabbrev ='" . $_POST['SalesType'] . "'
										AND prices.currabrev ='" . $_POST['CurrCode'] . "'
										AND prices.debtorno ='" . $_POST['DebtorNo_' . $PriceCounter] . "'
										AND prices.branchcode ='" . $_POST['BranchCode_' . $PriceCounter] . "'
										AND prices.startdate = CURRENT_DATE";
				$TestExistsResult = DB_query($SQLTestExists);
				if (DB_num_rows($TestExistsResult)==1){
					 //then we are updating
					$SQLUpdate = "UPDATE prices	SET price = '" . filter_number_format($_POST['Price_' . $PriceCounter]) . "'
									WHERE stockid = '" . $_POST['StockID_' . $PriceCounter] . "'
										AND prices.typeabbrev ='" . $_POST['SalesType'] . "'
										AND prices.currabrev ='" . $_POST['CurrCode'] . "'
										AND prices.debtorno ='" . $_POST['DebtorNo_' . $PriceCounter] . "'
										AND prices.branchcode ='" . $_POST['BranchCode_' . $PriceCounter] . "'
										AND prices.startdate = CURRENT_DATE
										AND prices.enddate ='" . $_POST['EndDate_' . $PriceCounter] . "'";
				$ResultUpdate = DB_query($SQLUpdate);
				} else { //there is not a price already starting today so need to create one
					//update the old price to have an end date of yesterday too
					$SQLUpdate = "UPDATE prices	SET enddate = '" . FormatDateForSQL(DateAdd(date($_SESSION['DefaultDateFormat']),'d',-1)) . "'
									WHERE stockid = '" . $_POST['StockID_' . $PriceCounter] . "'
										AND prices.typeabbrev ='" . $_POST['SalesType'] . "'
										AND prices.currabrev ='" . $_POST['CurrCode'] . "'
										AND prices.debtorno ='" . $_POST['DebtorNo_' . $PriceCounter] . "'
										AND prices.branchcode ='" . $_POST['BranchCode_' . $PriceCounter] . "'
										AND prices.startdate ='" . $_POST['StartDate_' . $PriceCounter] . "'
										AND prices.enddate ='" . $_POST['EndDate_' . $PriceCounter] . "'";
					$Result = DB_query($SQLUpdate);
					//we need to add a new price from today
					$SQLInsert = "INSERT INTO prices (	stockid,
														price,
														typeabbrev,
														currabrev,
														debtorno,
														branchcode,
														startdate
													) VALUES (
														'" . $_POST['StockID_' . $PriceCounter] . "',
														'" . filter_number_format($_POST['Price_' . $PriceCounter]) . "',
														'" . $_POST['SalesType'] . "',
														'" . $_POST['CurrCode'] . "',
														'" . $_POST['DebtorNo_' . $PriceCounter] . "',
														'" . $_POST['BranchCode_' . $PriceCounter] . "',
														CURRENT_DATE
													)";
					$ResultInsert = DB_query($SQLInsert);
				}
			}
			$PriceCounter++;
		}//end while loop
		DB_free_result($Result); //clear the old result
		$Result = DB_query($SQL); //re-run the query with the updated prices
		$NumRow = DB_num_rows($Result); // get the new number - should be the same!!
	}

	$SQLcat = "SELECT categorydescription
				FROM stockcategory
				WHERE categoryid='" . $_POST['StockCat'] . "'";
	$ResultCat = DB_query($SQLcat);
	$CategoryRow = DB_fetch_array($ResultCat);

	$SQLtype = "SELECT sales_type
				FROM salestypes
				WHERE typeabbrev='" . $_POST['SalesType'] . "'";
	$ResultType = DB_query($SQLtype);
	$SalesTypeRow = DB_fetch_array($ResultType);

	if (isset($CategoryRow['categorydescription'])) {
		$CategoryText = $CategoryRow['categorydescription'] . ' ' . __('category');
	} else {
		$CategoryText = __('all Categories');
	}

	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Pricing Review') . ': <span class="text-primary">' . $CategoryText . '</span></h3>
				<div class="db-badge db-badge-info">' . $NumRow . ' ' . __('Items Found') . '</div>
			</div>
			<div class="db-card-body p-0">';

	if ($NumRow > 0) {
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="update">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" value="' . $_POST['StockCat'] . '" name="StockCat" />
				<input type="hidden" value="' . $_POST['Margin'] . '" name="Margin" />
				<input type="hidden" value="' . $_POST['CurrCode'] . '" name="CurrCode" />
				<input type="hidden" value="' . $_POST['Comparator'] . '" name="Comparator" />
				<input type="hidden" value="' . $_POST['SalesType'] . '" name="SalesType" />

				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Item') . '</th>
								<th>' . __('Customer / Branch') . '</th>
								<th>' . __('Validity') . '</th>
								<th class="text-right">' . __('Cost') . '</th>
								<th class="text-center">' . __('GP %') . '</th>
								<th class="text-right">' . __('Proposed') . '</th>
								<th class="text-right" style="width: 150px;">' . __('List Price') . '</th>
							</tr>
						</thead>
						<tbody>';

		$PriceCounter = 0;
		while ($MyRow = DB_fetch_array($Result)) {
			$Cost = ($MyRow['cost'] == '') ? 0 : $MyRow['cost'];
			
			// Margin calc
			if ($MyRow['price'] != 0) {
				$CurrentGP = (($MyRow['price'] / $MyRow['rate']) - $Cost) * 100 / ($MyRow['price'] / $MyRow['rate']);
			} else {
				$CurrentGP = 0;
			}
			$ProposedPrice = $Cost * filter_number_format($_POST['Margin']);
			$EndDateDisplay = ($MyRow['enddate'] == '9999-12-31') ? __('Permanent') : ConvertSQLDate($MyRow['enddate']);
			
			$gpClass = ($CurrentGP < 10) ? 'db-badge-danger' : (($CurrentGP < 25) ? 'db-badge-warning' : 'db-badge-success');

			echo '<tr>
					<td>
						<div class="db-font-bold text-primary">' . $MyRow['stockid'] . '</div>
						<div class="db-muted" style="font-size: 0.75rem;">' . htmlspecialchars($MyRow['description']) . '</div>
					</td>
					<td>
						<div class="db-font-medium">' . ($MyRow['customer'] ?: __('General')) . '</div>
						<div class="db-muted" style="font-size: 0.75rem;">' . ($MyRow['branch'] ?: __('All Branches')) . '</div>
					</td>
					<td>
						<div style="font-size: 0.85rem;">' . ConvertSQLDate($MyRow['startdate']) . '</div>
						<div class="db-muted" style="font-size: 0.75rem;">→ ' . $EndDateDisplay . '</div>
					</td>
					<td class="text-right">' . locale_number_format($Cost, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td class="text-center">
						<span class="db-badge ' . $gpClass . '">' . locale_number_format($CurrentGP, 1) . '%</span>
					</td>
					<td class="text-right text-primary db-font-bold">' . locale_number_format($ProposedPrice, $MyRow['decimalplaces']) . '</td>
					<td class="text-right">
						<input type="hidden" value="' . $MyRow['stockid'] . '" name="StockID_' . $PriceCounter . '" />
						<input type="hidden" value="' . $MyRow['debtorno'] . '" name="DebtorNo_' . $PriceCounter . '" />
						<input type="hidden" value="' . $MyRow['branchcode'] . '" name="BranchCode_' . $PriceCounter . '" />
						<input type="hidden" value="' . $MyRow['startdate'] . '" name="StartDate_' . $PriceCounter . '" />
						<input type="hidden" value="' . $MyRow['enddate'] . '" name="EndDate_' . $PriceCounter . '" />
						<input type="text" class="db-input text-right p-1" name="Price_' . $PriceCounter . '" maxlength="14" style="width: 100%; height: 32px;" value="' . locale_number_format($MyRow['price'], $MyRow['decimalplaces']) . '" />
					</td>
				</tr>';
			$PriceCounter++;
		}

		echo '			</tbody>
					</table>
				</div>
				<div class="db-card-body border-top" style="display: flex; justify-content: flex-end; gap: 10px;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
						<i class="fas fa-undo"></i> ' . __('Reset') . '
					</a>
					<button type="submit" name="submit" value="' . __('Update') . '" class="db-btn db-btn-primary" onclick="return confirm(\'' . __('This will create new prices with commencement date of today and update existing historical prices. Are you sure?') . '\');">
						<i class="fas fa-save"></i> ' . __('Update All Prices') . '
					</button>
				</div>
			  </form>';
	} else {
		echo '<div class="text-center db-muted" style="padding: 60px;">
				<i class="fas fa-exclamation-circle fa-3x" style="margin-bottom: 20px; opacity: 0.3;"></i>
				<h4 class="db-font-bold">' . __('No Matches Found') . '</h4>
				<p>' . __('There were no prices meeting the specified criteria.') . '</p>
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-outline-primary" style="margin-top: 15px;">
					<i class="fas fa-arrow-left"></i> ' . __('Back to Search') . '
				</a>
			  </div>';
	}
	echo '	</div>
		  </div>';
} else {
	echo '<div class="db-card">
			<div class="db-card-body text-center" style="padding: 80px;">
				<div style="width: 80px; height: 80px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
					<i class="fas fa-tags fa-3x"></i>
				</div>
				<h3 class="db-font-bold">' . __('Bulk Price Update') . '</h3>
				<p class="db-muted" style="max-width: 450px; margin: 0 auto 25px;">' . __('Review and update selling prices in bulk based on a multiple of your current costs. Select your criteria from the sidebar to begin.') . '</p>
				<div class="db-badge db-badge-secondary">' . __('Step 1: Configure filters') . '</div>
				<i class="fas fa-arrow-right mx-2 db-muted"></i>
				<div class="db-badge db-badge-secondary">' . __('Step 2: Review margins') . '</div>
				<i class="fas fa-arrow-right mx-2 db-muted"></i>
				<div class="db-badge db-badge-secondary">' . __('Step 3: Save changes') . '</div>
			</div>
		  </div>';
}

echo '</main></div>';
include(__DIR__ . '/includes/footer.php');
