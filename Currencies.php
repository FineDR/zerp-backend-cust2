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

// Inject premium Architect styles
echo '<style>
    :root {
        --primary: #059669;
        --primary-dark: #065f46;
        --primary-light: #ecfdf5;
        --page-padding: 40px;
    }
    .db-page {
        padding: 0 var(--page-padding);
        max-width: 1600px;
        margin: 0 auto;
    }
    .premium-header { 
        margin-bottom: 30px; 
        padding: 24px 30px; 
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        gap: 20px;
    }
    .db-bottom-layout {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 32px;
        align-items: start;
        padding-bottom: 50px;
    }
    .arch-card { 
        background: #ffffff; 
        border-radius: 16px; 
        border: 1px solid #e5e7eb; 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 32px;
    }
    .arch-card-header { 
        background: #f9fafb; 
        border-bottom: 1px solid #f3f4f6; 
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }
    .arch-card-title {
        font-size: 0.95rem; font-weight: 850; color: #064e3b; margin:0;
        display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .arch-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 8px;
        background: #059669; color: #ffffff; border: none;
        font-weight: 700; font-size: 0.85rem; cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .arch-btn:hover { background: #065f46; transform: translateY(-1px); }
    .arch-btn-secondary { background: #f3f4f6; color: #374151; }
    .arch-btn-secondary:hover { background: #e5e7eb; }
    
    .arch-badge { padding: 4px 10px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; }
    .arch-badge-success { background: #dcfce7; color: #166534; }
    .arch-badge-neutral { background: #f3f4f6; color: #4b5563; }
    
    .arch-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px 50px;
    }
    .arch-form-field { margin-bottom: 24px; }
    .arch-form-label { display: block; font-size: 0.72rem; font-weight: 900; color: #064e3b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .arch-form-input { width: 100%; height: 48px; border-radius: 8px; border: 1.5px solid #d1fae5; padding: 0 16px; font-weight: 600; font-size: 0.95rem; transition: border-color 0.2s; }
    .arch-form-input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }

    .currency-list-item {
        padding: 16px 20px; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit;
    }
    .currency-list-item:hover { background: #f0fdf4; }
    .currency-list-item.active { background: #ecfdf5; border-left: 4px solid #059669; padding-left: 16px; }

    /* Summary Card Responsiveness */
    .summary-card-content {
        padding: 40px; color: #ffffff; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;
    }

    @media (max-width: 1024px) {
        .db-bottom-layout { grid-template-columns: 320px 1fr; gap: 20px; }
        :root { --page-padding: 20px; }
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .premium-header { position: relative; border-radius: 0; margin-left: calc(-1 * var(--page-padding)); margin-right: calc(-1 * var(--page-padding)); }
        .db-col-aside { order: 2; }
        .db-col-main { order: 1; }
    }

    @media (max-width: 640px) {
        :root { --page-padding: 15px; }
        .premium-header-inner { flex-direction: column; align-items: flex-start; }
        .arch-form-grid { grid-template-columns: 1fr; gap: 20px; }
        .summary-card-content { flex-direction: column; align-items: flex-start; gap: 20px; padding: 30px; }
        .summary-card-content h2 { font-size: 2rem !important; }
        .arch-card-header { padding: 15px 20px; flex-direction: column; align-items: flex-start; gap: 10px; }
        .db-card-body { padding: 20px !important; }
    }
</style>';

echo '<div class="db-page">
		<header class="premium-header">
			<div class="premium-header-inner">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-globe-africa"></i> ' . __('Internationalization') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.5;"></i> ' . __('Core Setup') . '
                    </div>
                    <h1 style="font-size: 2.2rem; font-weight: 950; letter-spacing: -1.5px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
                </div>
                <div>
                    <a href="' . $RootPath . '/SelectOrderItems.php" class="arch-btn arch-btn-secondary">
                        <i class="fas fa-arrow-left"></i> ' . __('Back to Orders') . '
                    </a>
                </div>
			</div>
		</header>';

include_once(__DIR__ . '/includes/CountriesArray.php');
include_once(__DIR__ . '/includes/CurrenciesArray.php');
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
	$InputError = 0;
	$i=1;

	$SQL="SELECT count(currabrev) FROM currencies WHERE currabrev='".$_POST['Abbreviation']."'";
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
		$SQLOldRate = "SELECT rate FROM currencies WHERE currabrev = '" . $SelectedCurrency . "'";
		$ResultOldRate = DB_query($SQLOldRate);
		$MyRow = DB_fetch_row($ResultOldRate);
		$OldRate = $MyRow[0];

		$SQL = "UPDATE currencies SET	country='". $_POST['Country']. "',
										hundredsname='" . $_POST['HundredsName'] . "',
										decimalplaces='" . filter_number_format($_POST['DecimalPlaces']) . "',
										rate='" .filter_number_format($_POST['ExchangeRate']) . "',
										webcart='" .$_POST['webcart'] . "'
					WHERE currabrev = '" . $SelectedCurrency . "'";
		$Msg = __('The currency definition record has been updated');
		$NewRate = $_POST['ExchangeRate'];

	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO currencies (currency, currabrev, country, hundredsname, decimalplaces, rate, webcart) 
                VALUES ('" . $CurrencyName[$_POST['Abbreviation']] . "', '" . $_POST['Abbreviation'] . "', '" . $_POST['Country'] . "', '" . $_POST['HundredsName'] .  "', '" . filter_number_format($_POST['DecimalPlaces']) . "', '" . filter_number_format($_POST['ExchangeRate']) . "', '" . $_POST['webcart'] . "')";
		$Msg = __('The currency definition record has been added');
	}

	DB_Txn_Begin();
	$Result = DB_query($SQL);
	if ($InputError!= 1) prnMsg($Msg,'success');

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
	$SQL= "SELECT COUNT(*) FROM debtorsmaster WHERE currcode = '" . $SelectedCurrency . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(__('Cannot delete this currency because customer accounts have been created referring to this currency'),'warn');
	} else {
		$SQL= "SELECT COUNT(*) FROM suppliers WHERE suppliers.currcode = '".$SelectedCurrency."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(__('Cannot delete this currency because supplier accounts have been created referring to this currency'),'warn');
		} else {
			$SQL= "SELECT COUNT(*) FROM banktrans WHERE currcode = '" . $SelectedCurrency . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				prnMsg(__('Cannot delete this currency because there are bank transactions that use this currency'),'warn');
			} elseif ($FunctionalCurrency==$SelectedCurrency) {
				prnMsg(__('Cannot delete this currency because it is the functional currency of the company'),'warn');
			} else {
				$SQL= "SELECT COUNT(*) FROM bankaccounts WHERE currcode = '" . $SelectedCurrency . "'";
				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);
				if ($MyRow[0] > 0) {
					prnMsg(__('Cannot delete this currency because there are bank accounts that use this currency'),'warn');
				} else {
					$SQL="DELETE FROM currencies WHERE currabrev='" . $SelectedCurrency . "'";
					$Result = DB_query($SQL);
					prnMsg(__('The currency definition record has been deleted'),'success');
				}
			}
		}
	}
}

// Start Dashboard Layout
echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 100px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-list-ul"></i> ' . __('All Currencies') . '</h3>
                </div>
                <div class="db-card-body" style="padding:0; max-height: calc(100vh - 250px); overflow-y: auto;">';

    $SQL = "SELECT currabrev, country, hundredsname, rate, decimalplaces, webcart FROM currencies ORDER BY currabrev";
    $Result = DB_query($SQL);
    
    while ($MyRow = DB_fetch_array($Result)) {
        $isActive = (isset($SelectedCurrency) && $SelectedCurrency == $MyRow['currabrev']) ? 'active' : '';
        $isFunctional = ($MyRow['currabrev'] == $FunctionalCurrency);
        
        $ImageFile = mb_strtoupper($MyRow['currabrev']) . '.gif';
        if (!file_exists('images/flags/' . $ImageFile)) $ImageFile = 'blank.gif';

        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCurrency=' . $MyRow['currabrev'] . '" class="currency-list-item ' . $isActive . '">
                <img alt="" src="' . $RootPath . '/images/flags/' . $ImageFile . '" style="width:20px; border-radius:2px;" />
                <div style="flex:1;">
                    <div style="font-weight: 800; font-size: 0.85rem;">' . $MyRow['currabrev'] . ' ' . ($isFunctional ? '<span class="arch-badge arch-badge-neutral" style="font-size:0.55rem; padding:2px 6px; background:#e0f2fe; color:#0369a1;">BASE</span>' : '') . '</div>
                    <div style="font-size: 0.72rem; color:#6b7280; font-weight:600;">' . $CurrencyName[$MyRow['currabrev']] . '</div>
                </div>
                <div class="text-right">
                    <div style="font-weight: 800; font-size: 0.8rem; font-family:var(--font-mono); color:#111827;">' . locale_number_format($MyRow['rate'], 'Variable') . '</div>
                </div>
              </a>';
    }

    echo '      </div>
            </div>
        </aside>

        <main class="db-col-main">
            <!-- Functional Currency Summary Card -->
            <div class="arch-card" style="background: linear-gradient(135deg, #059669 0%, #064e3b 100%); border: none;">
                <div class="summary-card-content">
                    <i class="fas fa-coins" style="font-size: 8rem; opacity: 0.1; position: absolute; right: -20px; top: -10px; transform: rotate(-15deg);"></i>
                    <div style="position: relative; z-index: 2;">
                        <div style="font-size: 0.72rem; font-weight: 850; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; margin-bottom: 8px;">System Functional Currency</div>
                        <h2 style="font-size: 2.8rem; font-weight: 950; margin: 0; color: #ffffff; line-height: 1; letter-spacing: -1.5px;">' . $FunctionalCurrency . '</h2>
                        <div style="font-size: 1.1rem; font-weight: 600; opacity: 0.9; margin-top: 5px;">' . $CurrencyName[$FunctionalCurrency] . '</div>
                    </div>
                    <div style="text-align: right; position: relative; z-index: 2;" class="summary-card-rate-box">
                        <div style="font-size: 0.72rem; font-weight: 850; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.8;">Base Exchange Rate</div>
                        <div style="font-size: 2rem; font-weight: 950; font-family: var(--font-mono); letter-spacing: -1px;">1.0000</div>
                    </div>
                </div>
            </div>';

    if (!isset($_GET['delete'])) {
        echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

        if (isset($SelectedCurrency) AND $SelectedCurrency != '') {
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

            $formTitle = __('Currency Maintenance');
            $formSubtitle = __('Updating') . ' <span style="color:#059669; font-weight:900;">' . $_POST['Abbreviation'] . '</span>';
            $formIcon = 'fas fa-edit';
        } else {
            if (!isset($_POST['Abbreviation'])) $_POST['Abbreviation'] = '';
            if (!isset($_POST['Country'])) $_POST['Country'] = '';
            if (!isset($_POST['HundredsName'])) $_POST['HundredsName'] = '';
            if (!isset($_POST['DecimalPlaces'])) $_POST['DecimalPlaces'] = 2;
            if (!isset($_POST['ExchangeRate'])) $_POST['ExchangeRate'] = 1;
            if (!isset($_POST['webcart'])) $_POST['webcart'] = 1;
            
            $formTitle = __('Add New Currency');
            $formSubtitle = __('Register a new international currency');
            $formIcon = 'fas fa-plus-circle';
        }

        echo '<div class="arch-card">
                <div class="arch-card-header">
                    <div>
                        <h3 class="arch-card-title"><i class="' . $formIcon . '" style="color:#059669;"></i> ' . $formTitle . '</h3>
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 5px; font-weight:600;">' . $formSubtitle . '</div>
                    </div>
                    ' . (isset($SelectedCurrency) ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary" style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca; padding:8px 16px;">
                        <i class="fas fa-times-circle"></i> ' . __('Exit Editor') . '
                    </a>' : '') . '
                </div>
                <div class="db-card-body">
                    <div class="arch-form-grid">';

        if (isset($SelectedCurrency)) {
            echo '<div class="arch-form-field">
                    <label class="arch-form-label">' . __('ISO 4217 Currency Code') . '</label>
                    <input type="text" class="arch-form-input" style="background:#f9fafb; color:#9ca3af; border-color:#e5e7eb; cursor:not-allowed;" value="' . $_POST['Abbreviation'] . '" disabled />
                </div>';
        } else {
            echo '<div class="arch-form-field">
                    <label class="arch-form-label">' . __('Select Currency') . '</label>
                    <select name="Abbreviation" class="arch-form-input" autofocus>';
            foreach ($CurrencyName as $CurrencyCode => $CurrencyNameTxt) {
                echo '<option value="' . $CurrencyCode . '">' . $CurrencyCode . ' - ' . $CurrencyNameTxt . '</option>';
            }
            echo '	</select>
                </div>';
        }

        echo '<div class="arch-form-field">
                <label class="arch-form-label">' . __('Country Name') . '</label>';
        if ($_POST['Abbreviation'] != $FunctionalCurrency) {
            echo '<input type="text" name="Country" class="arch-form-input" required maxlength="50" value="' . $_POST['Country'] . '" />';
        } else {
            echo '<input type="text" class="arch-form-input" style="background:#f9fafb; color:#9ca3af; border-color:#e5e7eb; cursor:not-allowed;" value="' . $_POST['Country'] . '" disabled />';
            echo '<input type="hidden" name="Country" value="' . $_POST['Country'] . '" />';
        }
        echo '</div>';

        echo '<div class="arch-form-field">
                <label class="arch-form-label">' . __('Hundredths Name') . '</label>
                <input type="text" name="HundredsName" class="arch-form-input" required maxlength="15" value="' . $_POST['HundredsName'] . '" />
            </div>';

        echo '<div class="arch-form-field">
                <label class="arch-form-label">' . __('Decimal Places') . '</label>
                <input type="number" name="DecimalPlaces" class="arch-form-input" required min="0" max="4" value="' . $_POST['DecimalPlaces'] . '" />
            </div>';

        echo '<div class="arch-form-field">
                <label class="arch-form-label">' . __('Exchange Rate') . '</label>';
        if ($_POST['Abbreviation'] != $FunctionalCurrency) {
            echo '<input type="text" name="ExchangeRate" class="arch-form-input number" required value="' . $_POST['ExchangeRate'] . '" />';
        } else {
            echo '<input type="text" class="arch-form-input" style="background:#f9fafb; color:#9ca3af; border-color:#e5e7eb; cursor:not-allowed;" value="' . $_POST['ExchangeRate'] . '" disabled />';
            echo '<input type="hidden" name="ExchangeRate" value="' . $_POST['ExchangeRate'] . '" />';
        }
        echo '</div>';

        echo '<div class="arch-form-field">
                <label class="arch-form-label">' . __('Show in webSHOP') . '</label>
                <select name="webcart" class="arch-form-input">
                    <option value="1" ' . (($_POST['webcart'] == 1) ? 'selected' : '') . '>' . __('Yes') . '</option>
                    <option value="0" ' . (($_POST['webcart'] == 0) ? 'selected' : '') . '>' . __('No') . '</option>
                </select>
            </div>
        </div></div>'; // End grid & db-card-body

        echo '<div style="padding: 32px 50px; background: #f9fafb; border-top: 1px solid #f3f4f6; display: flex; justify-content: center;" class="arch-form-footer">
                <button type="submit" name="submit" class="arch-btn" style="padding:16px 60px; font-size:1.05rem; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4);">
                    <i class="fas fa-check-double" style="margin-right:12px;"></i>
                    ' . (isset($SelectedCurrency) ? __('Update Currency') : __('Create Currency')) . '
                </button>
            </div>
        </div></form>';
    }

    echo '</main></div>'; // End db-bottom-layout
    echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
