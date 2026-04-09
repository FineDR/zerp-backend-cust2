<?php

//	Defines the currencies available. Each customer and supplier must be defined as transacting in one of the currencies defined here.
/*
	The country field is unneeded because the country_code is included inside the currency_code (firsts two letters).
*/

require(__DIR__ . '/includes/session.php');

$ViewTopic = 'Setup';
$BookMark = 'Currencies';
$Title = __('Currencies Maintenance');
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Manage international currencies and daily exchange rates') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

include_once(__DIR__ . '/includes/CountriesArray.php');// To get the country name from the country code.
include_once(__DIR__ . '/includes/CurrenciesArray.php');// To get the currency name from the currency code.
include_once(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['SelectedCurrency'])) {
	$SelectedCurrency = $_GET['SelectedCurrency'];
} elseif (isset($_POST['SelectedCurrency'])) {
	$SelectedCurrency = $_POST['SelectedCurrency'];
}

$ForceConfigReload = true;
include(__DIR__ . '/includes/GetConfig.php');
$ForceConfigReload = false;

$FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];

$Errors = array();



if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs are sensible
	$i=1;

	$SQL="SELECT count(currabrev)
			FROM currencies
			WHERE currabrev='".$_POST['Abbreviation']."'";

	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);

	if ($MyRow[0]!= 0 AND !isset($SelectedCurrency)) {
		$InputError = 1;
		prnMsg( __('The currency already exists in the database'),'error');
		$Errors[$i] = 'Abbreviation';
		$i++;
	}

	if (!is_numeric(filter_number_format($_POST['ExchangeRate']))) {
		$InputError = 1;
		prnMsg(__('The exchange rate must be numeric'),'error');
		$Errors[$i] = 'ExchangeRate';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['DecimalPlaces']))) {
		$InputError = 1;
	   prnMsg(__('The number of decimal places to display for amounts in this currency must be numeric'),'error');
		$Errors[$i] = 'DecimalPlaces';
		$i++;
	} elseif (filter_number_format($_POST['DecimalPlaces'])<0) {
		$InputError = 1;
	   prnMsg(__('The number of decimal places to display for amounts in this currency must be positive or zero'),'error');
		$Errors[$i] = 'DecimalPlaces';
		$i++;
	} elseif (filter_number_format($_POST['DecimalPlaces'])>4) {
		$InputError = 1;
	   prnMsg(__('The number of decimal places to display for amounts in this currency is expected to be 4 or less'),'error');
		$Errors[$i] = 'DecimalPlaces';
		$i++;
	}

	if (mb_strlen($_POST['Country']) > 50) {
		$InputError = 1;
		prnMsg(__('The currency country must be 50 characters or less long'),'error');
		$Errors[$i] = 'Country';
		$i++;
	}
	if (mb_strlen($_POST['HundredsName']) > 15) {
		$InputError = 1;
		prnMsg(__('The hundredths name must be 15 characters or less long'),'error');
		$Errors[$i] = 'HundredsName';
		$i++;
	}
	if (($FunctionalCurrency !=  '') AND (isset($SelectedCurrency) AND $SelectedCurrency==$FunctionalCurrency)) {
		$_POST['ExchangeRate'] = 1;
	}

	if (isset($SelectedCurrency) AND $InputError != 1) {
		/*Get the previous exchange rate. We will need it later to adjust bank account balances */
		$SQLOldRate = "SELECT rate
				FROM currencies
				WHERE currabrev = '" . $SelectedCurrency . "'";
		$ResultOldRate = DB_query($SQLOldRate);
		$MyRow = DB_fetch_row($ResultOldRate);
		$OldRate = $MyRow[0];

		/*SelectedCurrency could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/
		$SQL = "UPDATE currencies SET	country='". $_POST['Country']. "',
										hundredsname='" . $_POST['HundredsName'] . "',
										decimalplaces='" . filter_number_format($_POST['DecimalPlaces']) . "',
										rate='" .filter_number_format($_POST['ExchangeRate']) . "',
										webcart='" .$_POST['webcart'] . "'
					WHERE currabrev = '" . $SelectedCurrency . "'";
		$Msg = __('The currency definition record has been updated');
		$NewRate = $_POST['ExchangeRate'];

	} elseif ($InputError != 1) {

	/*Selected currencies is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new payment terms form */
		$SQL = "INSERT INTO currencies (
						currency,
						currabrev,
						country,
						hundredsname,
						decimalplaces,
						rate,
						webcart
					) VALUES ('" .
						$CurrencyName[$_POST['Abbreviation']] . "', '" .
						$_POST['Abbreviation'] . "', '" .
						$_POST['Country'] . "', '" .
						$_POST['HundredsName'] .  "', '" .
						filter_number_format($_POST['DecimalPlaces']) . "', '" .
						filter_number_format($_POST['ExchangeRate']) . "', '" .
						$_POST['webcart'] . "')";
		$Msg = __('The currency definition record has been added');
	}
	//run the SQL from either of the above possibilites

	DB_Txn_Begin();

	$Result = DB_query($SQL);
	if ($InputError!= 1) {
		prnMsg( $Msg,'success');
	}

	if (isset($SelectedCurrency) AND $InputError != 1) {
		AdjustBankAccountsDueToCurrencyExchangeRate($SelectedCurrency, $OldRate, $NewRate);
	}
	
	DB_Txn_Commit();

	unset($SelectedCurrency);
	unset($_POST['Country']);
	unset($_POST['HundredsName']);
	unset($_POST['DecimalPlaces']);
	unset($_POST['ExchangeRate']);
	unset($_POST['Abbreviation']);
	unset($_POST['webcart']);

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN DebtorsMaster

	$SQL= "SELECT COUNT(*) FROM debtorsmaster
			WHERE currcode = '" . $SelectedCurrency . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0)
	{
		prnMsg(__('Cannot delete this currency because customer accounts have been created referring to this currency') .
		 	'<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('customer accounts that refer to this currency'),'warn');
	} else {
		$SQL= "SELECT COUNT(*) FROM suppliers
				WHERE suppliers.currcode = '".$SelectedCurrency."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(__('Cannot delete this currency because supplier accounts have been created referring to this currency')
			 . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('supplier accounts that refer to this currency'),'warn');
		} else {
			$SQL= "SELECT COUNT(*) FROM banktrans
					WHERE currcode = '" . $SelectedCurrency . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				prnMsg(__('Cannot delete this currency because there are bank transactions that use this currency') .
				'<br />' . ' ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('bank transactions that refer to this currency'),'warn');
			} elseif ($FunctionalCurrency==$SelectedCurrency) {
				prnMsg(__('Cannot delete this currency because it is the functional currency of the company'),'warn');
			} else {
				$SQL= "SELECT COUNT(*) FROM bankaccounts
					WHERE currcode = '" . $SelectedCurrency . "'";
				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);
				if ($MyRow[0] > 0) {
					prnMsg(__('Cannot delete this currency because there are bank accounts that use this currency') .
					'<br />' . ' ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('bank accounts that refer to this currency'),'warn');
				} else {
					//only delete if used in neither customer or supplier, comp prefs, bank trans accounts
					$SQL="DELETE FROM currencies WHERE currabrev='" . $SelectedCurrency . "'";
					$Result = DB_query($SQL);
					prnMsg(__('The currency definition record has been deleted'),'success');
				}
			}
		}
	}
	//end if currency used in customer or supplier accounts
}

if (!isset($SelectedCurrency)) {

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedCurrency will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
then none of the above are true and the list of payment termss will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$SQL = "SELECT	currabrev,
					country,
					hundredsname,
					rate,
					decimalplaces,
					webcart
				FROM currencies";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
					' . __('Defined Currencies') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th style="width:40px;"></th>
								<th>' . __('Code / Country') . '</th>
								<th>' . __('Currency Name') . '</th>
								<th class="text-right">' . __('Exchange Rate') . '</th>
								<th class="text-right">' . __('Live Rate') . '</th>
								<th class="text-center">' . __('WebSHOP') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	/*Get published currency rates from Eurpoean Central Bank */
	if ($_SESSION['UpdateCurrencyRatesDaily'] != '0') {
		if ($_SESSION['ExchangeRateFeed'] == 'ECB') {
			$CurrencyRatesArray = GetECBCurrencyRates();
		} elseif ($_SESSION['ExchangeRateFeed'] == 'DXR') {
			$CurrencyRatesArray = GetDXRCurrencyRates();
		} else {
			$CurrencyRatesArray = array();
		}
	} else {
		$CurrencyRatesArray = array();
	}

	while ($MyRow = DB_fetch_array($Result)) {
		$isFunctional = ($MyRow['currabrev'] == $FunctionalCurrency);
		$rowClass = $isFunctional ? 'style="background: var(--primary-lightest);"' : '';
		
		$ImageFile = mb_strtoupper($MyRow['currabrev']) . '.gif';
		if (!file_exists('images/flags/' . $ImageFile)) {
			$ImageFile = 'blank.gif';
		}

		$Rate = GetCurrencyRate($MyRow['currabrev'], $CurrencyRatesArray);
		if ($Rate == 0) $Rate = 1;

		$webBadge = ($MyRow['webcart'] == 1) 
			? '<span class="db-badge db-badge-success">' . __('Yes') . '</span>'
			: '<span class="db-badge db-badge-ghost">' . __('No') . '</span>';

		echo '<tr ' . $rowClass . '>
				<td><img alt="" src="' . $RootPath . '/images/flags/' . $ImageFile . '" style="border-radius:2px; box-shadow:0 1px 3px rgba(0,0,0,0.1);" /></td>
				<td>
					<div class="font-bold">' . $MyRow['currabrev'] . '</div>
					<div class="text-xs text-muted">' . $CountriesArray[substr($MyRow['currabrev'], 0, 2)] . '</div>
				</td>
				<td>
					<div>' . $CurrencyName[$MyRow['currabrev']] . '</div>
					<div class="text-xs text-muted">' . $MyRow['hundredsname'] . ' (' . $MyRow['decimalplaces'] . ' d.p.)</div>
				</td>
				<td class="text-right">
					<div class="font-mono">' . locale_number_format($MyRow['rate'], 'Variable') . '</div>
					<div class="text-xs text-muted">1 / ' . locale_number_format(1 / $MyRow['rate'], 4) . '</div>
				</td>
				<td class="text-right">
					<span class="db-badge ' . (($Rate != $MyRow['rate'] && !$isFunctional) ? 'db-badge-warning' : 'db-badge-ghost') . ' font-mono">' . locale_number_format($Rate, 4) . '</span>
				</td>
				<td class="text-center">' . $webBadge . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCurrency=' . $MyRow['currabrev'] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>';
		
		if (!$isFunctional) {
			echo '		<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCurrency=' . $MyRow['currabrev'] . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this currency?') . '\');">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						</a>';
		}
		
		echo '			<a href="' . $RootPath . '/ExchangeRateTrend.php?CurrencyToShow=' . $MyRow['currabrev'] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Graph') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
						</a>
					</div>
				</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';
} //end of ifs and buts!


	if (isset($SelectedCurrency)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Show all currency definitions') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedCurrency) AND $SelectedCurrency != '') {
			// Editing an existing currency
			$SQL = "SELECT currabrev, country, hundredsname, decimalplaces, rate, webcart FROM currencies WHERE currabrev='" . $SelectedCurrency . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);

			$_POST['Abbreviation'] = $MyRow['currabrev'];
			$_POST['Country'] = $MyRow['country'];
			$_POST['HundredsName'] = $MyRow['hundredsname'];
			$_POST['ExchangeRate'] = locale_number_format($MyRow['rate'], 'Variable');
			$_POST['DecimalPlaces'] = locale_number_format($MyRow['decimalplaces'], 0);
			$_POST['webcart'] = $MyRow['webcart'];

			echo '<input type="hidden" name="SelectedCurrency" value="' . $SelectedCurrency . '" />';
			echo '<input type="hidden" name="Abbreviation" value="' . $_POST['Abbreviation'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Edit Currency Details') . ': ' . $_POST['Abbreviation'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('ISO 4217 Currency Code') . '</label>
								<input type="text" class="db-input" value="' . $_POST['Abbreviation'] . '" disabled />
							</div>';

		} else {
			if (!isset($_POST['Abbreviation'])) $_POST['Abbreviation'] = '';
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('New Currency Details') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Select Currency') . '</label>
								<select name="Abbreviation" class="db-input" autofocus>';
			foreach ($CurrencyName as $CurrencyCode => $CurrencyNameTxt) {
				echo '<option value="' . $CurrencyCode . '">' . $CurrencyCode . ' - ' . $CurrencyNameTxt . '</option>';
			}
			echo '				</select>
							</div>';
		}

		if (!isset($_POST['Country'])) $_POST['Country'] = '';
		if (!isset($_POST['HundredsName'])) $_POST['HundredsName'] = '';
		if (!isset($_POST['DecimalPlaces'])) $_POST['DecimalPlaces'] = 2;
		if (!isset($_POST['ExchangeRate'])) $_POST['ExchangeRate'] = 1;
		if (!isset($_POST['webcart'])) $_POST['webcart'] = 1;

		echo '<div class="db-field">
				<label class="db-label">' . __('Country Name') . '</label>';
		if ($_POST['Abbreviation'] != $FunctionalCurrency) {
			echo '<input type="text" name="Country" class="db-input" required maxlength="50" value="' . $_POST['Country'] . '" />';
		} else {
			echo '<input type="text" class="db-input" value="' . $_POST['Country'] . '" disabled />';
			echo '<input type="hidden" name="Country" value="' . $_POST['Country'] . '" />';
		}
		echo '</div>';

		echo '<div class="db-field">
				<label class="db-label">' . __('Hundredths Name') . '</label>
				<input type="text" name="HundredsName" class="db-input" required maxlength="15" value="' . $_POST['HundredsName'] . '" />
			</div>';

		echo '<div class="db-field">
				<label class="db-label">' . __('Decimal Places') . '</label>
				<input type="number" name="DecimalPlaces" class="db-input" required min="0" max="4" value="' . $_POST['DecimalPlaces'] . '" />
			</div>';

		echo '<div class="db-field">
				<label class="db-label">' . __('Exchange Rate') . '</label>';
		if ($_POST['Abbreviation'] != $FunctionalCurrency) {
			echo '<input type="text" name="ExchangeRate" class="db-input number" required value="' . $_POST['ExchangeRate'] . '" />';
		} else {
			echo '<input type="text" class="db-input" value="' . $_POST['ExchangeRate'] . '" disabled />';
			echo '<input type="hidden" name="ExchangeRate" value="' . $_POST['ExchangeRate'] . '" />';
		}
		echo '</div>';

		echo '<div class="db-field">
				<label class="db-label">' . __('Show in webSHOP') . '</label>
				<select name="webcart" class="db-input">
					<option value="1" ' . (($_POST['webcart'] == 1) ? 'selected' : '') . '>' . __('Yes') . '</option>
					<option value="0" ' . (($_POST['webcart'] == 0) ? 'selected' : '') . '>' . __('No') . '</option>
				</select>
			</div>
		</div></div>'; // End db-grid & db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Currency Information') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page

} //end if record deleted no point displaying form to add record

include(__DIR__ . '/includes/footer.php');
