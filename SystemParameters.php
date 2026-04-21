<?php

/* This script is for maintenance of the system parameters. */

require(__DIR__ . '/includes/session.php');

$Title = __('System Parameters');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'SystemParameters';

// Inject premium styles for the Architect workspace
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { 
        margin-bottom: 30px; 
        padding: 20px; 
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        margin: 0 0 30px 0;
        border-radius: 12px;
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
    }
	
	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-sm);
		overflow: hidden;
        margin-bottom: 30px;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 24px 30px;
	}
	.db-card-title {
		font-size: 1rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
    .db-card-body { padding: 40px; }
	
    /* Target legacy webERP field structure */
    fieldset { border: none; padding: 0; margin: 0; }
    legend { display: none; }
    
    field {
        display: block;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f9fafb;
    }
    field:last-child { border-bottom: none; }
    
    field label {
        font-size: 0.75rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 1.2px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 10px;
        line-height: 1.4;
        overflow-wrap: break-word;
        white-space: normal;
    }
    field input[type="text"], 
    field input[type="email"],
    field input[type="number"], 
    field select, 
    field textarea {
        width: 100%; border-radius: 8px; height: 50px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 20px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
    }
    field textarea { height: 100px; padding: 15px 20px; }
    field input:focus, field select:focus, field textarea:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    
    fieldhelp { font-size: 0.8rem; color: #6b7280; margin-top: 8px; font-weight: 500; display: block; line-height: 1.5; }

	.architect-btn {
		display: inline-flex; align-items: center; gap: 10px;
		padding: 12px 28px; border-radius: 8px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 280px 1fr; 
        gap: 40px; 
        align-items: start; 
    }

    .tab-menu { display: flex; flex-direction: column; gap: 8px; position: sticky; top: 20px; }
    .tab-item {
        display: flex; align-items: center; gap: 16px; padding: 18px 24px;
        background: transparent; border-radius: 10px; border: none;
        color: #4b5563; font-weight: 700; font-size: 0.9rem; text-align: left;
        cursor: pointer; transition: all 0.25s ease;
    }
    .tab-item:hover { background: #f0fdf4; color: #059669; }
    .tab-item.active { background: #059669; color: #ffffff; box-shadow: 0 10px 20px rgba(5, 150, 105, 0.15); }
    .tab-item i:not(.status-icon) { font-size: 1.1rem; width: 24px; text-align: center; }
    
    .tab-item.locked { opacity: 0.5; pointer-events: none; filter: grayscale(1); }
    .tab-item.completed i.status-icon { color: #059669; }
    .tab-item .status-icon { margin-left: auto; font-size: 0.8rem; opacity: 0.6; }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: slideUp 0.4s ease-out; }

    @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .next-btn {
        margin-top: 40px; display: flex; justify-content: flex-end; gap: 16px;
        padding-top: 30px; border-top: 1px solid #f3f4f6;
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .tab-menu { 
            flex-direction: column; 
            padding: 0; 
            position: static; 
            background: transparent;
            margin: 0 0 20px 0;
            gap: 12px;
        }
        .tab-item { width: 100%; white-space: normal; padding: 18px 24px; }
    }

    @media (max-width: 768px) {
        .db-page { padding: var(--space-2) var(--space-1); }
        .db-card-body { padding: 25px 20px; }
        .premium-header { padding: 15px; margin-bottom: 20px; }
        .premium-header-inner { flex-direction: column; align-items: flex-start; gap: 15px; }
        .premium-header h1 { font-size: 1.4rem; }
        .architect-btn { padding: 12px 20px; font-size: 0.85rem; width: 100%; justify-content: center; }
        field label { font-size: 0.7rem; }
        field input, field select { height: 45px; font-size: 0.9rem; }
        .next-btn { flex-direction: column; gap: 12px; }
    }

    @media (max-width: 480px) {
        .premium-header h1 { font-size: 1.2rem; }
        .tab-item { padding: 10px 15px; font-size: 0.8rem; }
        .tab-item i:not(.status-icon) { font-size: 0.9rem; width: 18px; }
    }
</style>
<script>
    const tabRequirements = {
        \'general\': [\'X_DefaultDateFormat\', \'X_PageLength\'],
        \'accounting\': [\'X_YearEnd\', \'X_PastDueDays1\', \'X_DefaultCreditLimit\'],
        \'sales\': [\'X_QuickEntries\', \'X_DefaultPriceList\'],
        \'purchasing\': [\'X_AutoAuthorisePO\'],
        \'inventory\': [\'X_WeightedAverageCosting\', \'X_ProhibitNegativeStock\']
    };

    const tabOrder = [\'general\', \'accounting\', \'sales\', \'purchasing\', \'inventory\', \'system\'];

    function isTabComplete(tabId) {
        const fields = tabRequirements[tabId];
        if (!fields) return true;
        return fields.every(fieldName => {
            const el = document.querySelector(\'[name="\' + fieldName + \'"]\');
            return el && el.value.trim() !== \'\';
        });
    }

    function updateWizardState() {
        let allPreviousCompleted = true;
        let completedCount = 0;
        tabOrder.forEach((tabId, index) => {
            const tabItem = document.getElementById(\'tab-\' + tabId);
            if (!tabItem) return;
            const statusIcon = tabItem.querySelector(\'.status-icon\');
            const complete = isTabComplete(tabId);
            
            if (complete) {
                tabItem.classList.add(\'completed\');
                statusIcon.className = \'fas fa-check-circle status-icon\';
                completedCount++;
            } else {
                tabItem.classList.remove(\'completed\');
                statusIcon.className = \'far fa-circle status-icon\';
            }

            if (index === 0 || allPreviousCompleted) {
                tabItem.classList.remove(\'locked\');
                tabItem.style.display = \'flex\';
            } else {
                tabItem.classList.add(\'locked\');
                statusIcon.className = \'fas fa-lock status-icon\';
                tabItem.style.display = \'none\';
            }

            if (!complete) {
                allPreviousCompleted = false;
            }
        });

        // Update Progress Bar
        const progress = Math.round((completedCount / tabOrder.length) * 100);
        const progressBar = document.getElementById(\'progress-bar\');
        const progressPercent = document.getElementById(\'progress-percent\');
        if (progressBar) progressBar.style.width = progress + \'%\';
        if (progressPercent) progressPercent.innerText = progress + \'%\';
    }

    function switchTab(tabId) {
        const tabIdx = tabOrder.indexOf(tabId);
        
        // Find the index of the first incomplete tab
        let firstIncompleteIdx = -1;
        for (let i = 0; i < tabOrder.length; i++) {
            if (!isTabComplete(tabOrder[i])) {
                firstIncompleteIdx = i;
                break;
            }
        }

        // Allow clicking current, previous, or the very next step (if current is complete)
        if (firstIncompleteIdx !== -1 && tabIdx > firstIncompleteIdx) {
            alert("Please complete " + tabOrder[firstIncompleteIdx].charAt(0).toUpperCase() + tabOrder[firstIncompleteIdx].slice(1) + " section first.");
            return;
        }

        document.querySelectorAll(\'.tab-panel\').forEach(el => el.classList.remove(\'active\'));
        document.querySelectorAll(\'.tab-item\').forEach(el => el.classList.remove(\'active\'));
        document.getElementById(\'panel-\' + tabId).classList.add(\'active\');
        document.getElementById(\'tab-\' + tabId).classList.add(\'active\');
        window.scrollTo({ top: 0, behavior: \'smooth\' });
    }

    document.addEventListener(\'DOMContentLoaded\', () => {
        updateWizardState();
        document.querySelectorAll(\'input, select, textarea\').forEach(input => {
            input.addEventListener(\'input\', updateWizardState);
            input.addEventListener(\'change\', updateWizardState);
        });
    });
</script>';

include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/CountriesArray.php');
if (empty($_SESSION['ItemDescriptionLanguages']) or $_SESSION['ItemDescriptionLanguages'] == '') {
	$_SESSION['ItemDescriptionLanguages'] = ',';
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	// validate inputs are sensible
	/*
		Note: the X_ in the POST variables, the reason for this is to overcome globals=on replacing
		the actual system/overridden variables.
	*/
	if (mb_strlen($_POST['X_PastDueDays1']) > 3 OR !is_numeric($_POST['X_PastDueDays1']) ) {
		$InputError = 1;
		prnMsg(__('First overdue deadline days must be a number'),'error');
	} elseif (mb_strlen($_POST['X_PastDueDays2'])  > 3 OR !is_numeric($_POST['X_PastDueDays2']) ) {
		$InputError = 1;
		prnMsg(__('Second overdue deadline days must be a number'),'error');
	} elseif (mb_strlen($_POST['X_DefaultCreditLimit']) > 12 OR !is_numeric($_POST['X_DefaultCreditLimit']) ) {
		$InputError = 1;
		prnMsg(__('Default Credit Limit must be a number'),'error');
	} elseif (mb_strstr($_POST['X_RomalpaClause'], "'") OR mb_strlen($_POST['X_RomalpaClause']) > 5000) {
		$InputError = 1;
		prnMsg(__('The Romalpa Clause may not contain single quotes and may not be longer than 5000 chars'),'error');
	} elseif (mb_strlen($_POST['X_QuickEntries']) > 2 OR !is_numeric($_POST['X_QuickEntries']) OR
		$_POST['X_QuickEntries'] < 1 OR $_POST['X_QuickEntries'] > 99 ) {
		$InputError = 1;
		prnMsg(__('No less than 1 and more than 99 Quick entries allowed'),'error');
	} elseif (!is_numeric($_POST['X_MaxSerialItemsIssued']) or $_POST['X_MaxSerialItemsIssued'] < 1) {
		$InputError = 1;
		prnMsg(__('The maximum number of serial numbers issued must be numeric and greater than zero'), 'error');
	} elseif (mb_strlen($_POST['X_FreightChargeAppliesIfLessThan']) > 12 OR !is_numeric($_POST['X_FreightChargeAppliesIfLessThan']) ) {
		$InputError = 1;
		prnMsg(__('Freight Charge Applies If Less Than must be a number'),'error');
	} elseif ( !is_numeric($_POST['X_StandardCostDecimalPlaces']) OR
		$_POST['X_StandardCostDecimalPlaces'] < 0 OR $_POST['X_StandardCostDecimalPlaces'] > 4 ) {
		$InputError = 1;
		prnMsg(__('Standard Cost Decimal Places must be a number between 0 and 4'),'error');
	} elseif (mb_strlen($_POST['X_NumberOfPeriodsOfStockUsage']) > 2 OR !is_numeric($_POST['X_NumberOfPeriodsOfStockUsage']) OR
		$_POST['X_NumberOfPeriodsOfStockUsage'] < 1 OR $_POST['X_NumberOfPeriodsOfStockUsage'] > 12 ) {
		$InputError = 1;
		prnMsg(__('Financial period per year must be a number between 1 and 12'),'error');
	} elseif (!in_array(intval($_POST['X_StockUsageShowZeroWithinPeriodRange']), [0, 1])) {
		$InputError = 1;
		prnMsg(__('Unexpected Show Zero Counts Within Stock Usage Graph Period Range value.'), 'error');
	} elseif (mb_strlen($_POST['X_TaxAuthorityReferenceName']) >25) {
		$InputError = 1;
		prnMsg(__('The Tax Authority Reference Name must be 25 characters or less long'),'error');
	} elseif (mb_strlen($_POST['X_OverChargeProportion']) > 3 OR !is_numeric($_POST['X_OverChargeProportion']) OR
		$_POST['X_OverChargeProportion'] < 0 OR $_POST['X_OverChargeProportion'] > 100 ) {
		$InputError = 1;
		prnMsg(__('Over Charge Proportion must be a percentage'),'error');
	} elseif (mb_strlen($_POST['X_OverReceiveProportion']) > 3 OR !is_numeric($_POST['X_OverReceiveProportion']) OR
		$_POST['X_OverReceiveProportion'] < 0 OR $_POST['X_OverReceiveProportion'] > 100 ) {
		$InputError = 1;
		prnMsg(__('Over Receive Proportion must be a percentage'),'error');
	} elseif (mb_strlen($_POST['X_PageLength']) > 3 OR !is_numeric($_POST['X_PageLength']) OR
		$_POST['X_PageLength'] < 1 ) {
		$InputError = 1;
		prnMsg(__('Lines per page must be greater than 1'),'error');
	} elseif (mb_strlen($_POST['X_MonthsAuditTrail']) > 2 OR !is_numeric($_POST['X_MonthsAuditTrail']) OR
		$_POST['X_MonthsAuditTrail'] < 0 ) {
		$InputError = 1;
		prnMsg(__('The number of months of audit trail to keep must be zero or a positive number less than 100 months'),'error');
	} elseif (mb_strlen($_POST['X_DefaultTaxCategory']) > 1 OR !is_numeric($_POST['X_DefaultTaxCategory']) OR
		$_POST['X_DefaultTaxCategory'] < 1 ) {
		$InputError = 1;
		prnMsg(__('DefaultTaxCategory must be between 1 and 9'),'error');
	} elseif (mb_strlen($_POST['X_DefaultDisplayRecordsMax']) > 3 OR !is_numeric($_POST['X_DefaultDisplayRecordsMax']) OR
		$_POST['X_DefaultDisplayRecordsMax'] < 1 ) {
		$InputError = 1;
		prnMsg(__('Default maximum number of records to display must be between 1 and 500'),'error');
	} elseif (mb_strlen($_POST['X_MaxImageSize']) > 4 OR !is_numeric($_POST['X_MaxImageSize']) OR
		$_POST['X_MaxImageSize'] < 1 ) {
		$InputError = 1;
		prnMsg(__('The maximum size of item image files must be between 1 KB and 9999 KB'),'error');
	} elseif (mb_strlen($_POST['X_FrequentlyOrderedItems']) > 2 OR !is_numeric($_POST['X_FrequentlyOrderedItems'])) {
		$InputError = 1;
		prnMsg(__('The number of frequently ordered items to display must be numeric'),'error');
	} elseif (strlen($_POST['X_SmtpSetting']) != 1 OR !is_numeric($_POST['X_SmtpSetting'])){
		$InputError = 1;
		prnMsg(__('The SMTP setting should be selected as Yes or No'),'error');
	} elseif (strlen($_POST['X_QualityLogSamples']) != 1 OR !is_numeric($_POST['X_QualityLogSamples'])){
		$InputError = 1;
		prnMsg(__('The Quality Log Samples setting should be selected as Yes or No'),'error');
	} elseif (mb_strstr($_POST['X_QualityProdSpecText'], "'") OR mb_strlen($_POST['X_QualityProdSpecText']) > 5000) {
		$InputError = 1;
		prnMsg(__('The Quality ProdSpec Text may not contain single quotes and may not be longer than 5000 chars'),'error');
	} elseif (mb_strstr($_POST['X_QualityCOAText'], "'") OR mb_strlen($_POST['X_QualityCOAText']) > 5000) {
		$InputError = 1;
		prnMsg(__('The Quality COA Text may not contain single quotes and may not be longer than 5000 chars'),'error');
	}

	if ($InputError !=1){

		$SQL = array();

		if ($_SESSION['DefaultDateFormat'] != $_POST['X_DefaultDateFormat'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultDateFormat']."' WHERE confname = 'DefaultDateFormat'";
		}
		if ($DefaultTheme != $_POST['X_DefaultTheme']) {// If not equal, update the default theme.
			// BEGIN: Update the config.php file:
			$FileHandle = fopen($PathPrefix . 'config.php', 'r');
			if ($FileHandle) {
				$Content = fread($FileHandle, filesize('config.php'));
				$Content = str_replace(' ;\n', ';\n', $Content);// Clean space before the end-of-php-line.
				$Content = str_replace('\''.$DefaultTheme .'\';', '\''.$_POST['X_DefaultTheme'].'\';', $Content);
				$FileHandle = fopen($PathPrefix . 'config.php','w');
				if (!fwrite($FileHandle,$Content)) {
					prnMsg(__('Cannot write to the configuration file.'), 'error');
				} else {
					prnMsg(__('The configuration file was updated.'), 'info');
				}
				fclose($FileHandle);
			} else {
				prnMsg(__('Cannot open the configuration file.'), 'error');
			}
			// END: Update the config.php file.
		}
		if ($_SESSION['PastDueDays1'] != $_POST['X_PastDueDays1'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_PastDueDays1']."' WHERE confname = 'PastDueDays1'";
		}
		if ($_SESSION['PastDueDays2'] != $_POST['X_PastDueDays2'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_PastDueDays2']."' WHERE confname = 'PastDueDays2'";
		}
		if ($_SESSION['DefaultCreditLimit'] != $_POST['X_DefaultCreditLimit'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultCreditLimit']."' WHERE confname = 'DefaultCreditLimit'";
		}
		if ($_SESSION['Show_Settled_LastMonth'] != $_POST['X_Show_Settled_LastMonth'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_Show_Settled_LastMonth']."' WHERE confname = 'Show_Settled_LastMonth'";
		}
		if ($_SESSION['RomalpaClause'] != $_POST['X_RomalpaClause'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_RomalpaClause'] . "' WHERE confname = 'RomalpaClause'";
		}
		if ($_SESSION['QuickEntries'] != $_POST['X_QuickEntries'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_QuickEntries']."' WHERE confname = 'QuickEntries'";
		}
		if ($_SESSION['MaxSerialItemsIssued'] != $_POST['X_MaxSerialItemsIssued']) {
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_MaxSerialItemsIssued'] . "' WHERE confname = 'MaxSerialItemsIssued'";
		}
		if ($_SESSION['WorkingDaysWeek'] != $_POST['X_WorkingDaysWeek'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_WorkingDaysWeek']."' WHERE confname = 'WorkingDaysWeek'";
		}
		if ($_SESSION['DispatchCutOffTime'] != $_POST['X_DispatchCutOffTime'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DispatchCutOffTime']."' WHERE confname = 'DispatchCutOffTime'";
		}
		if ($_SESSION['AllowSalesOfZeroCostItems'] != $_POST['X_AllowSalesOfZeroCostItems'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_AllowSalesOfZeroCostItems']."' WHERE confname = 'AllowSalesOfZeroCostItems'";
		}
		if ($_SESSION['CreditingControlledItems_MustExist'] != $_POST['X_CreditingControlledItems_MustExist'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_CreditingControlledItems_MustExist']."' WHERE confname = 'CreditingControlledItems_MustExist'";
		}
		if ($_SESSION['DefaultPriceList'] != $_POST['X_DefaultPriceList'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultPriceList']."' WHERE confname = 'DefaultPriceList'";
		}
		if ($_SESSION['Default_Shipper'] != $_POST['X_Default_Shipper'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_Default_Shipper']."' WHERE confname = 'Default_Shipper'";
		}
		if ($_SESSION['DoFreightCalc'] != $_POST['X_DoFreightCalc'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DoFreightCalc']."' WHERE confname = 'DoFreightCalc'";
		}
		if ($_SESSION['FreightChargeAppliesIfLessThan'] != $_POST['X_FreightChargeAppliesIfLessThan'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_FreightChargeAppliesIfLessThan']."' WHERE confname = 'FreightChargeAppliesIfLessThan'";
		}
		if ($_SESSION['DefaultTaxCategory'] != $_POST['X_DefaultTaxCategory'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultTaxCategory']."' WHERE confname = 'DefaultTaxCategory'";
		}
		if ($_SESSION['TaxAuthorityReferenceName'] != $_POST['X_TaxAuthorityReferenceName'] ) {
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_TaxAuthorityReferenceName'] . "' WHERE confname = 'TaxAuthorityReferenceName'";
		}
		if ($_SESSION['CountryOfOperation'] != $_POST['X_CountryOfOperation'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_CountryOfOperation'] ."' WHERE confname = 'CountryOfOperation'";
		}
		if ($_SESSION['StandardCostDecimalPlaces'] != $_POST['X_StandardCostDecimalPlaces'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_StandardCostDecimalPlaces']."' WHERE confname = 'StandardCostDecimalPlaces'";
		}
		if ($_SESSION['NumberOfPeriodsOfStockUsage'] != $_POST['X_NumberOfPeriodsOfStockUsage'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_NumberOfPeriodsOfStockUsage']."' WHERE confname = 'NumberOfPeriodsOfStockUsage'";
		}
		if ($_SESSION['StockUsageShowZeroWithinPeriodRange'] != $_POST['X_StockUsageShowZeroWithinPeriodRange']) {
			$SQL[] = "UPDATE config SET confvalue = '" . intval($_POST['X_StockUsageShowZeroWithinPeriodRange']) . "' WHERE confname = 'StockUsageShowZeroWithinPeriodRange'";
		}
		if ($_SESSION['Check_Qty_Charged_vs_Del_Qty'] != $_POST['X_Check_Qty_Charged_vs_Del_Qty'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_Check_Qty_Charged_vs_Del_Qty']."' WHERE confname = 'Check_Qty_Charged_vs_Del_Qty'";
		}
		if ($_SESSION['Check_Price_Charged_vs_Order_Price'] != $_POST['X_Check_Price_Charged_vs_Order_Price'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_Check_Price_Charged_vs_Order_Price']."' WHERE confname = 'Check_Price_Charged_vs_Order_Price'";
		}
		if ($_SESSION['OverChargeProportion'] != $_POST['X_OverChargeProportion'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_OverChargeProportion']."' WHERE confname = 'OverChargeProportion'";
		}
		if ($_SESSION['OverReceiveProportion'] != $_POST['X_OverReceiveProportion'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_OverReceiveProportion']."' WHERE confname = 'OverReceiveProportion'";
		}
		if ($_SESSION['PO_AllowSameItemMultipleTimes'] != $_POST['X_PO_AllowSameItemMultipleTimes'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_PO_AllowSameItemMultipleTimes']."' WHERE confname = 'PO_AllowSameItemMultipleTimes'";
		}
		if ($_SESSION['SO_AllowSameItemMultipleTimes'] != $_POST['X_SO_AllowSameItemMultipleTimes'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_SO_AllowSameItemMultipleTimes']."' WHERE confname = 'SO_AllowSameItemMultipleTimes'";
		}
		if ($_SESSION['YearEnd'] != $_POST['X_YearEnd'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_YearEnd']."' WHERE confname = 'YearEnd'";
		}
		if ($_SESSION['PageLength'] != $_POST['X_PageLength'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_PageLength']."' WHERE confname = 'PageLength'";
		}
		if ($_SESSION['DefaultDisplayRecordsMax'] != $_POST['X_DefaultDisplayRecordsMax'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultDisplayRecordsMax']."' WHERE confname = 'DefaultDisplayRecordsMax'";
		}
		if ($_SESSION['MaxImageSize'] != $_POST['X_MaxImageSize'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_MaxImageSize']."' WHERE confname = 'MaxImageSize'";
		}
		if ($_SESSION['ShowStockidOnImages'] != $_POST['X_ShowStockidOnImages'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_ShowStockidOnImages']."' WHERE confname = 'ShowStockidOnImages'";
		}
		//new number must be shown
		if ($_SESSION['NumberOfMonthMustBeShown'] != $_POST['X_NumberOfMonthMustBeShown'] ) {
			$SQL[] = "UPDATE config SET confvalue = '".$_POST['X_NumberOfMonthMustBeShown']."' WHERE confname = 'NumberOfMonthMustBeShown'";
		}
		if ($_SESSION['part_pics_dir'] != $_POST['X_part_pics_dir'] ) {
			$SQL[] = "UPDATE config SET confvalue = 'companies/" . $_SESSION['DatabaseName'] . '/' . $_POST['X_part_pics_dir']."' WHERE confname = 'part_pics_dir'";
		}
		if ($_SESSION['reports_dir'] != $_POST['X_reports_dir'] ) {
			$SQL[] = "UPDATE config SET confvalue = 'companies/" . $_SESSION['DatabaseName'] . '/' . $_POST['X_reports_dir']."' WHERE confname = 'reports_dir'";
		}
		if ($_SESSION['AutoDebtorNo'] != $_POST['X_AutoDebtorNo'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_AutoDebtorNo'])."' WHERE confname = 'AutoDebtorNo'";
		}
		if ($_SESSION['AutoSupplierNo'] != $_POST['X_AutoSupplierNo'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_AutoSupplierNo'])."' WHERE confname = 'AutoSupplierNo'";
		}
		if ($_SESSION['HTTPS_Only'] != $_POST['X_HTTPS_Only'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_HTTPS_Only'])."' WHERE confname = 'HTTPS_Only'";
		}
		if ($_SESSION['DB_Maintenance'] != $_POST['X_DB_Maintenance'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_DB_Maintenance'])."' WHERE confname = 'DB_Maintenance'";
		}
		if ($_SESSION['DefaultBlindPackNote'] != $_POST['X_DefaultBlindPackNote'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_DefaultBlindPackNote'])."' WHERE confname = 'DefaultBlindPackNote'";
		}
		if ($_SESSION['ShowValueOnGRN'] != $_POST['X_ShowValueOnGRN'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_ShowValueOnGRN'])."' WHERE confname = 'ShowValueOnGRN'";
		}
		if ($_SESSION['PackNoteFormat'] != $_POST['X_PackNoteFormat'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_PackNoteFormat'])."' WHERE confname = 'PackNoteFormat'";
		}
		if ($_SESSION['CheckCreditLimits'] != $_POST['X_CheckCreditLimits'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". ($_POST['X_CheckCreditLimits'])."' WHERE confname = 'CheckCreditLimits'";
		}
		if ($_SESSION['WikiApp'] !== $_POST['X_WikiApp'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_WikiApp']."' WHERE confname = 'WikiApp'";
		}
		if ($_SESSION['WikiPath'] != $_POST['X_WikiPath'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_WikiPath']."' WHERE confname = 'WikiPath'";
		}
		if ($_SESSION['ProhibitJournalsToControlAccounts'] != $_POST['X_ProhibitJournalsToControlAccounts'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_ProhibitJournalsToControlAccounts']."' WHERE confname = 'ProhibitJournalsToControlAccounts'";
		}
		if ($_SESSION['InvoiceQuantityDefault'] != $_POST['X_InvoiceQuantityDefault'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_InvoiceQuantityDefault']."' WHERE confname = 'InvoiceQuantityDefault'";
		}
		if ($_SESSION['InvoicePortraitFormat'] != $_POST['X_InvoicePortraitFormat'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_InvoicePortraitFormat']."' WHERE confname = 'InvoicePortraitFormat'";
		}
		if ($_SESSION['AllowOrderLineItemNarrative'] != $_POST['X_AllowOrderLineItemNarrative'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_AllowOrderLineItemNarrative']."' WHERE confname = 'AllowOrderLineItemNarrative'";
		}
		if ($_SESSION['GoogleTranslatorAPIKey'] != $_POST['X_GoogleTranslatorAPIKey'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_GoogleTranslatorAPIKey']."' WHERE confname = 'GoogleTranslatorAPIKey'";
		}
		if ($_SESSION['RequirePickingNote'] != $_POST['X_RequirePickingNote'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_RequirePickingNote']."' WHERE confname = 'RequirePickingNote'";
		}
		if ($_SESSION['geocode_integration'] != $_POST['X_geocode_integration'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_geocode_integration']."' WHERE confname = 'geocode_integration'";
		}
		if ($_SESSION['Extended_SupplierInfo'] != $_POST['X_Extended_SupplierInfo'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_Extended_SupplierInfo']."' WHERE confname = 'Extended_SupplierInfo'";
		}
		if ($_SESSION['Extended_CustomerInfo'] != $_POST['X_Extended_CustomerInfo'] ) {
			$SQL[] = "UPDATE config SET confvalue = '". $_POST['X_Extended_CustomerInfo']."' WHERE confname = 'Extended_CustomerInfo'";
		}
		if ($_SESSION['ProhibitPostingsBefore'] != $_POST['X_ProhibitPostingsBefore'] ) {
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_ProhibitPostingsBefore']."' WHERE confname = 'ProhibitPostingsBefore'";
		}
		if ($_SESSION['WeightedAverageCosting'] != $_POST['X_WeightedAverageCosting'] ) {
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_WeightedAverageCosting']."' WHERE confname = 'WeightedAverageCosting'";
		}
		if ($_SESSION['AutoIssue'] != $_POST['X_AutoIssue']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_AutoIssue'] . "' WHERE confname='AutoIssue'";
		}
		if ($_SESSION['ProhibitNegativeStock'] != $_POST['X_ProhibitNegativeStock']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_ProhibitNegativeStock'] . "' WHERE confname='ProhibitNegativeStock'";
		}
		if ($_SESSION['MonthsAuditTrail'] != $_POST['X_MonthsAuditTrail']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_MonthsAuditTrail'] . "' WHERE confname='MonthsAuditTrail'";
		}
		if ($_SESSION['LogSeverity'] != $_POST['X_LogSeverity']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_LogSeverity'] . "' WHERE confname='LogSeverity'";
		}
		if ($_SESSION['LogPath'] != $_POST['X_LogPath']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_LogPath'] . "' WHERE confname='LogPath'";
		}
		if ($_SESSION['UpdateCurrencyRatesDaily'] != $_POST['X_UpdateCurrencyRatesDaily']){
			if ($_POST['X_UpdateCurrencyRatesDaily']==1) {
				$SQL[] = "UPDATE config SET confvalue= CURRENT_DATE WHERE confname='UpdateCurrencyRatesDaily'";
			} else {
				$SQL[] = "UPDATE config SET confvalue='0' WHERE confname='UpdateCurrencyRatesDaily'";
			}
		}
		if ($_SESSION['ExchangeRateFeed'] != $_POST['X_ExchangeRateFeed']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_ExchangeRateFeed'] . "' WHERE confname='ExchangeRateFeed'";
		}
		if ($_SESSION['FactoryManagerEmail'] != $_POST['X_FactoryManagerEmail']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_FactoryManagerEmail'] . "' WHERE confname='FactoryManagerEmail'";
		}
		if ($_SESSION['PurchasingManagerEmail'] != $_POST['X_PurchasingManagerEmail']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_PurchasingManagerEmail'] . "' WHERE confname='PurchasingManagerEmail'";
		}
		if ($_SESSION['InventoryManagerEmail'] != $_POST['X_InventoryManagerEmail']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_InventoryManagerEmail'] . "' WHERE confname='InventoryManagerEmail'";
		}
		if ($_SESSION['AutoCreateWOs'] != $_POST['X_AutoCreateWOs']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_AutoCreateWOs'] . "' WHERE confname='AutoCreateWOs'";
		}
		if ($_SESSION['DefaultFactoryLocation'] != $_POST['X_DefaultFactoryLocation']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_DefaultFactoryLocation'] . "' WHERE confname='DefaultFactoryLocation'";
		}
		if ($_SESSION['DefineControlledOnWOEntry'] != $_POST['X_DefineControlledOnWOEntry']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_DefineControlledOnWOEntry'] . "' WHERE confname='DefineControlledOnWOEntry'";
		}
		if ($_SESSION['FrequentlyOrderedItems'] != $_POST['X_FrequentlyOrderedItems']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_FrequentlyOrderedItems'] . "' WHERE confname='FrequentlyOrderedItems'";
		}
		if ($_SESSION['AutoAuthorisePO'] != $_POST['X_AutoAuthorisePO']){
			$SQL[] = "UPDATE config SET confvalue='" . $_POST['X_AutoAuthorisePO'] . "' WHERE confname='AutoAuthorisePO'";
		}
		if (isset($_POST['X_ItemDescriptionLanguages'])) {
			$ItemDescriptionLanguages = '';
			foreach ($_POST['X_ItemDescriptionLanguages'] as $ItemLanguage){
				$ItemDescriptionLanguages .= $ItemLanguage .',';
			}
			if ($_SESSION['ItemDescriptionLanguages'] != $ItemDescriptionLanguages){
				$SQL[] = "UPDATE config SET confvalue='" . $ItemDescriptionLanguages . "' WHERE confname='ItemDescriptionLanguages'";
			}
		}
		if ($_SESSION['SmtpSetting'] != $_POST['X_SmtpSetting']){
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_SmtpSetting'] . "' WHERE confname='SmtpSetting'";
		}
		if ($_SESSION['QualityLogSamples'] != $_POST['X_QualityLogSamples']){
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_QualityLogSamples'] . "' WHERE confname='QualityLogSamples'";

		}
		if ($_SESSION['QualityProdSpecText'] != $_POST['X_QualityProdSpecText']){
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_QualityProdSpecText'] . "' WHERE confname='QualityProdSpecText'";
		}
		if ($_SESSION['QualityCOAText'] != $_POST['X_QualityCOAText']){
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_QualityCOAText'] . "' WHERE confname='QualityCOAText'";
		}
		if ($_SESSION['ShortcutMenu'] != $_POST['X_ShortcutMenu']){
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_ShortcutMenu'] . "' WHERE confname='ShortcutMenu'";
		}
		if ($_SESSION['LastDayOfWeek'] != $_POST['X_LastDayOfWeek']){
			$SQL[] = "UPDATE config SET confvalue = '" . $_POST['X_LastDayOfWeek'] . "' WHERE confname='LastDayOfWeek'";
		}

		$ErrMsg =  __('The system configuration could not be updated because');
		if (sizeof($SQL) > 1 ) {
			DB_Txn_Begin();
			foreach ($SQL as $Line) {
				$Result = DB_query($Line, $ErrMsg);
			}
			DB_Txn_Commit();
		} elseif (sizeof($SQL)==1) {
			$Result = DB_query($SQL, $ErrMsg);
		}

		prnMsg( __('System configuration updated'),'success');

		$ForceConfigReload = true; // Required to force a load even if stored in the session vars
		include(__DIR__ . '/includes/GetConfig.php');
		$ForceConfigReload = false;
	} else {
		prnMsg( __('Validation failed') . ', ' . __('no updates or deletes took place'),'warn');
	}

} /* end of if submit */

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div>
					<div style="font-size: 0.75rem; font-weight: 800; color: #6b7280; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.6;">
						<i class="fas fa-tools"></i> ' . __('Setup') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem;"></i> ' . __('Core Configuration') . '
					</div>
					<h1 style="font-size: 2.2rem; font-weight: 950; letter-spacing: -1.5px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
				</div>
                <div>
                     <button type="submit" form="main-form" name="submit" class="architect-btn">
                        <i class="fas fa-cloud-upload-alt"></i> ' . __('Update System Parameters') . '
                    </button>
                </div>
			</div>
		</div>';

echo '<form id="main-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
        
        <div class="db-bottom-layout">
            <aside class="db-sidebar">
                <div class="progress-guide" style="margin-bottom: 25px; padding: 20px; background: #f0fdf4; border-radius: 12px; border: 1px solid #d1fae5;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 850; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                        <span>Progress</span>
                        <span id="progress-percent">0%</span>
                    </div>
                    <div style="height: 6px; background: #ffffff; border-radius: 10px; overflow: hidden;">
                        <div id="progress-bar" style="height: 100%; background: #059669; width: 0%; transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                    </div>
                </div>
                <nav class="tab-menu">
                    <button type="button" id="tab-general" class="tab-item active" onclick="switchTab(\'general\')">
                        <i class="fas fa-desktop"></i> ' . __('1. General & Display') . '
                        <i class="far fa-circle status-icon"></i>
                    </button>
                    <button type="button" id="tab-accounting" class="tab-item locked" onclick="switchTab(\'accounting\')">
                        <i class="fas fa-calculator"></i> ' . __('2. Accounting') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-sales" class="tab-item locked" onclick="switchTab(\'sales\')">
                        <i class="fas fa-shopping-cart"></i> ' . __('3. Sales & Invoicing') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-purchasing" class="tab-item locked" onclick="switchTab(\'purchasing\')">
                        <i class="fas fa-truck-loading"></i> ' . __('4. Purchasing & QMS') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-inventory" class="tab-item locked" onclick="switchTab(\'inventory\')">
                        <i class="fas fa-boxes"></i> ' . __('5. Inventory & WO') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-system" class="tab-item locked" onclick="switchTab(\'system\')">
                        <i class="fas fa-server"></i> ' . __('6. System & Integration') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                </nav>
            </aside>

            <main class="db-main">
                <!-- Panel 1: General -->
                <div id="panel-general" class="tab-panel active">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-eye"></i> ' . __('Display & Formatting') . '</h3></div>
                        <div class="db-card-body">';

                // DefaultDateFormat
                echo '<field>
                        <label for="X_DefaultDateFormat">' . __('Default Date Format') . ':</label>
                        <select name="X_DefaultDateFormat">
                            <option '.(($_SESSION['DefaultDateFormat']=='d/m/Y')?'selected="selected" ':'').'value="d/m/Y">' . __('d/m/Y') . '</option>
                            <option '.(($_SESSION['DefaultDateFormat']=='d.m.Y')?'selected="selected" ':'').'value="d.m.Y">' . __('d.m.Y') . '</option>
                            <option '.(($_SESSION['DefaultDateFormat']=='m/d/Y')?'selected="selected" ':'').'value="m/d/Y">' . __('m/d/Y') . '</option>
                            <option '.(($_SESSION['DefaultDateFormat']=='Y/m/d')?'selected="selected" ':'').'value="Y/m/d">' . __('Y/m/d') . '</option>
                            <option '.(($_SESSION['DefaultDateFormat']=='Y-m-d')?'selected="selected" ':'').'value="Y-m-d">' . __('Y-m-d') . '</option>
                        </select>
                        <fieldhelp>' . __('The default date format for entry of dates and display.') . '</fieldhelp>
                    </field>';

                // PageLength
                echo '<field>
                        <label for="X_PageLength">' . __('Report Page Length') . ':</label>
                        <input type="text" class="integer" pattern="(?!^0\d*$)[\d]{1,3}" title="'.__('The input should be between 1 and 999').'" placeholder="'.__('1 to 999').'" name="X_PageLength" size="4" maxlength="6" value="' . $_SESSION['PageLength'] . '" />
                        <fieldhelp>' . __('Default lines per page for PDF reports.') . '</fieldhelp>
                    </field>';

                // DefaultDisplayRecordsMax
                echo '<field>
                        <label for="X_DefaultDisplayRecordsMax">' . __('Default Maximum Records to Show') . ':</label>
                        <input type="text" class="integer" pattern="(?!^0\d*$)[\d]{1,3}" required="required" title="'.__('The records should be between 1 and 999').'" name="X_DefaultDisplayRecordsMax" size="4" maxlength="3" value="' . $_SESSION['DefaultDisplayRecordsMax'] . '" />
                        <fieldhelp>' . __('When pages have code to limit the number of returned records, this will be the default number to show.') . '</fieldhelp>
                    </field>';

                // MonthsAuditTrail
                echo '<field>
                        <label for="X_MonthsAuditTrail">' . __('Months of Audit Trail to Retain') . ':</label>
                        <input type="text" class="integer" pattern="(?!^0\d+$)[\d]{1,2}" required="required" name="X_MonthsAuditTrail" size="3" maxlength="2" value="' . $_SESSION['MonthsAuditTrail'] . '" />
                        <fieldhelp>' . __('A log of which users performed which additions, updates, and deletes of database records. 0 means disabled.') . '</fieldhelp>
                    </field>';

                // Log Severity
                $SeverityOptions = [ __('None'), __('Errors Only'), __('Errors and Warnings'), __('Errors, Warnings and Info'), __('All') ];
                echo '<field>
                        <label for="X_LogSeverity">' . __('Log Severity Level') . ':</label>
                        <select name="X_LogSeverity" >';
                foreach ($SeverityOptions as $key => $Value) {
                    echo '<option value="' . $key . '"' . ($_SESSION['LogSeverity'] == $key ? ' selected' : '') . '>' . $Value . '</option>';
                }
                echo '</select><fieldhelp>' . __('Choose which status messages to keep in your log file.') . '</fieldhelp></field>';

                // Log Path
                echo '<field>
                        <label for="X_LogPath">' . __('Path to log files') . ':</label>
                        <input type="text" name="X_LogPath" size="40" maxlength="79" value="' . $_SESSION['LogPath'] . '" />
                        <fieldhelp>' . __('The directory path where log files will be stored. Must be writable by the web server.') . '</fieldhelp>
                    </field>';

                // DefaultTheme
                if (is_writable('config.php')) {
                    echo '<field>
                            <label for="X_DefaultTheme">' . __('Default Theme') . ':</label>
                            <select name="X_DefaultTheme">';
                    $ThemeDirectories = scandir($PathPrefix . 'css');
                    foreach($ThemeDirectories as $ThemeName) {
                        if (is_dir('css/'.$ThemeName) AND $ThemeName!='.' AND $ThemeName!='..' AND $ThemeName!='.svn') {
                            echo '<option ' . ($DefaultTheme == $ThemeName ? 'selected="selected"' : '') . ' value="'. $ThemeName.'">' . $ThemeName . '</option>';
                        }
                    }
                    echo '</select><fieldhelp>' . __('The default theme for the login screen and new user setups.') . '</fieldhelp></field>';
                } else {
                    echo '<input type="hidden" name="X_DefaultTheme" value="' . $DefaultTheme . '" />';
                }

                // ItemDescriptionLanguages
                if (!isset($_POST['X_ItemDescriptionLanguages'])){
                    $_POST['X_ItemDescriptionLanguages'] = explode(',',$_SESSION['ItemDescriptionLanguages']);
                }
                echo '</select><fieldhelp>' . __('Select languages for multi-lingual item descriptions.') . '</fieldhelp></field>';

                // Additional General Settings
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Advanced UI Preferences') . '</h4>
                        <field>
                            <label for="X_DefaultDisplayRecordsMax">' . __('Max Records to Display') . ':</label>
                            <input type="text" class="integer" name="X_DefaultDisplayRecordsMax" value="' . $_SESSION['DefaultDisplayRecordsMax'] . '" />
                            <fieldhelp>' . __('Default limit for records displayed in search screens.') . '</fieldhelp>
                        </field>
                        <field>
                            <label for="X_ShowStockidOnImages">' . __('Show Item Code on Thumbnails') . ':</label>
                            <select name="X_ShowStockidOnImages">
                                <option value="1"' . ($_SESSION['ShowStockidOnImages'] ? ' selected="selected" ' : '') . '>' . __('Yes') . '</option>
                                <option value="0"' . (!$_SESSION['ShowStockidOnImages'] ? ' selected="selected" ' : '') . '>' . __('No') . '</option>
                            </select>
                        </field>
                        <field>
                            <label for="X_NumberOfMonthMustBeShown">' . __('Sales Inquiry Months Selection') . ':</label>
                            <input type="text" class="integer" name="X_NumberOfMonthMustBeShown" value="' . $_SESSION['NumberOfMonthMustBeShown'] . '" />
                            <fieldhelp>' . __('Default number of months shown in sales inquiry selection.') . '</fieldhelp>
                        </field>
                        <field>
                            <label for="X_LastDayOfWeek">' . __('First Day of the Week') . ':</label>
                            <select name="X_LastDayOfWeek">
                                <option value="0"' . ($_SESSION['LastDayOfWeek']==0 ? ' selected':'') . '>' . __('Sunday') . '</option>
                                <option value="1"' . ($_SESSION['LastDayOfWeek']==1 ? ' selected':'') . '>' . __('Monday') . '</option>
                            </select>
                        </field>
                    </div>';

echo '          <div class="next-btn">
                    <button type="button" class="architect-btn" onclick="switchTab(\'accounting\')">' . __('Next: Accounting') . ' <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
      </div>';

/* Panel 2: Accounting */
echo '<!-- Panel 2: Accounting -->
      <div id="panel-accounting" class="tab-panel">
        <div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('Accounting & Finance Rules') . '</h3></div>
            <div class="db-card-body">';

                // YearEnd
                $MonthNames = array( 1=>__('January'), 2=>__('February'), 3=>__('March'), 4=>__('April'), 5=>__('May'), 6=>__('June'), 7=>__('July'), 8=>__('August'), 9=>__('September'), 10=>__('October'), 11=>__('November'), 12=>__('December') );
                echo '<field>
                        <label for="X_YearEnd">' . __('Financial Year Ends On') . ':</label>
                        <select name="X_YearEnd">';
                for ($i=1; $i <= sizeof($MonthNames); $i++ )
                    echo '<option value="' . $i . '"' . ($_SESSION['YearEnd'] == $i ? ' selected="selected"' : '') . '>' . $MonthNames[$i] . '</option>';
                echo '</select><fieldhelp>' . __('The month in which the financial year ends.')  . '</fieldhelp></field>';

                // Overdue Days
                echo '<field>
                        <label for="X_PastDueDays1">' . __('First Overdue Deadline (days)') . ':</label>
                        <input type="text" class="integer" required="required" pattern="(?!^0\d+$)[\d]+" name="X_PastDueDays1" value="' . $_SESSION['PastDueDays1'] . '" size="3" maxlength="3" />
                        <fieldhelp>' . __('Balances displayed as overdue by this many days on inquiry screens.') . '</fieldhelp>
                    </field>';
                echo '<field>
                        <label for="X_PastDueDays2">' . __('Second Overdue Deadline (days)') . ':</label>
                        <input type="text" class="integer" required="required" pattern="(?!^0\d+$)[\d]+" name="X_PastDueDays2" value="' . $_SESSION['PastDueDays2'] . '" size="3" maxlength="3" />
                    </field>';

                // Credit Limits
                echo '<field>
                        <label for="X_DefaultCreditLimit">' . __('Default Credit Limit') . ':</label>
                        <input type="text" class="number" required="required" name="X_DefaultCreditLimit" value="' . $_SESSION['DefaultCreditLimit'] . '" size="12" maxlength="12" />
                        <fieldhelp>' . __('Initial credit limit for new customers.') . '</fieldhelp>
                    </field>';
                echo '<field>
                        <label for="X_CheckCreditLimits">' . __('Check Credit Limits') . ':</label>
                        <select name="X_CheckCreditLimits">
                            <option value="0"' . ($_SESSION['CheckCreditLimits'] == 0 ? ' selected="selected"' : '') . '>' . __('Do not check') . '</option>
                            <option value="1"' . ($_SESSION['CheckCreditLimits'] == 1 ? ' selected="selected"' : '') . '>' . __('Warn on breach') . '</option>
                            <option value="2"' . ($_SESSION['CheckCreditLimits'] == 2 ? ' selected="selected"' : '') . '>' . __('Prohibit Sales') . '</option>
                        </select>
                        <fieldhelp>' . __('How to handle orders that exceed customer credit limits.') . '</fieldhelp>
                    </field>';

                // Show Settled Last Month
                echo '<field>
                        <label for="X_Show_Settled_LastMonth">' . __('Show Settled Last Month') . ':</label>
                        <select name="X_Show_Settled_LastMonth">
                            <option value="1"' . ($_SESSION['Show_Settled_LastMonth'] ? ' selected="selected"' : '') .'>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['Show_Settled_LastMonth'] ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                        <fieldhelp>' . __('Show paid/settled transactions on customer statements.') . '</fieldhelp>
                    </field>';

                // Romalpa
                echo '<field>
                        <label for="X_RomalpaClause">' . __('Romalpa Clause') . ':</label>
                        <textarea name="X_RomalpaClause" rows="3" cols="40">' . $_SESSION['RomalpaClause'] . '</textarea>
                        <fieldhelp>' . __('Small print for invoices/credits regarding ownership of goods.') . '</fieldhelp>
                    </field>';

                // Control Account Journals
                echo '<field>
                        <label for="X_ProhibitJournalsToControlAccounts">' . __('Prohibit GL Journals to Control Accounts') . ':</label>
                        <select name="X_ProhibitJournalsToControlAccounts">';
                if ($_SESSION['ProhibitJournalsToControlAccounts']=='1'){
                        echo  '<option selected="selected" value="1">' . __('Prohibited') . '</option>';
                        echo  '<option value="0">' . __('Allowed') . '</option>';
                } else {
                        echo  '<option value="1">' . __('Prohibited') . '</option>';
                        echo  '<option selected="selected" value="0">' . __('Allowed') . '</option>';
                }
                echo '</select><fieldhelp>' . __('Prevent accidental journals to AR/AP control accounts.') . '</fieldhelp></field>';

                // Prohibit Postings
                echo '<field>
                        <label for="X_ProhibitPostingsBefore">' . __('Prohibit GL Postings Prior To') . ':</label>
                        <select name="X_ProhibitPostingsBefore">';
                $SQL = "SELECT lastdate_in_period FROM periods ORDER BY periodno DESC";
                $Result = DB_query($SQL);
                if ($_SESSION['ProhibitPostingsBefore']=='' OR $_SESSION['ProhibitPostingsBefore']=='1900-01-01' OR !isset($_SESSION['ProhibitPostingsBefore'])){
                    echo '<option selected="selected" value="1900-01-01">' . ConvertSQLDate('1900-01-01') . '</option>';
                }
                while ($Row = DB_fetch_row($Result)){
                    echo '<option ' . ($_SESSION['ProhibitPostingsBefore']==$Row[0] ? 'selected="selected"' : '') . ' value="' . $Row[0] . '">' . ConvertSQLDate($Row[0]) . '</option>';
                }
                echo '</select><fieldhelp>' . __('Lock historical periods from further postings.') . '</fieldhelp></field>';

echo '          <div class="next-btn">
                    <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'general\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                    <button type="button" class="architect-btn" onclick="switchTab(\'sales\')">' . __('Next: Sales & Invoicing') . ' <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
      </div>';

/* Panel 3: Sales */
echo '<!-- Panel 3: Sales -->
      <div id="panel-sales" class="tab-panel">
        <div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-shopping-cart"></i> ' . __('Sales & Invoicing Defaults') . '</h3></div>
            <div class="db-card-body">';

                // Quick Entries
                echo '<field>
                        <label for="X_QuickEntries">' . __('Quick Entries Count') . ':</label>
                        <input type="text" class="integer" pattern="[1-9][\d]{0,1}" name="X_QuickEntries" value="' . $_SESSION['QuickEntries'] . '" size="3" maxlength="2" />
                        <fieldhelp>' . __('Layout of sales order entry - number of fields for quick entry.') . '</fieldhelp>
                    </field>';

                // Frequently Ordered
                echo '<field>
                        <label for="X_FrequentlyOrderedItems">' . __('Frequently Ordered Items Count') . ':</label>
                        <input type="text" class="integer" pattern="(?!^0[1-9]+$)[\d]{1,2}" name="X_FrequentlyOrderedItems" value="' . $_SESSION['FrequentlyOrderedItems'] . '" size="3" maxlength="2" />
                        <fieldhelp>' . __('Number of items to show in frequently ordered list.') . '</fieldhelp>
                    </field>';

                // Multiples
                echo '<field>
                        <label for="X_SO_AllowSameItemMultipleTimes">' . __('Allow Same Item Multiple Times') . ':</label>
                        <select name="X_SO_AllowSameItemMultipleTimes">
                            <option value="1"' . ($_SESSION['SO_AllowSameItemMultipleTimes'] ? ' selected="selected"' : '') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['SO_AllowSameItemMultipleTimes'] ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                    </field>';

                // Narrative
                echo '<field>
                        <label for="X_AllowOrderLineItemNarrative">' . __('Allow Line Item Narrative') . ':</label>
                        <select name="X_AllowOrderLineItemNarrative">
                            <option value="1"' . ($_SESSION['AllowOrderLineItemNarrative'] == '1' ? ' selected="selected"' : '') . '>' . __('Yes') . '</option>
                            <option value="0"' . ($_SESSION['AllowOrderLineItemNarrative'] == '0' ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                    </field>';

                // Invoicing
                echo '<field>
                        <label for="X_InvoicePortraitFormat">' . __('Invoice Orientation') . ':</label>
                        <select name="X_InvoicePortraitFormat">
                            <option value="0"' . ($_SESSION['InvoicePortraitFormat'] == '0' ? ' selected="selected"' : '') . '>' . __('Landscape') . '</option>
                            <option value="1"' . ($_SESSION['InvoicePortraitFormat'] == '1' ? ' selected="selected"' : '') . '>' . __('Portrait') . '</option>
                        </select>
                    </field>';
                echo '<field>
                        <label for="X_InvoiceQuantityDefault">' . __('Invoice Quantity Default') . ':</label>
                        <select name="X_InvoiceQuantityDefault">
                            <option value="0"' . ($_SESSION['InvoiceQuantityDefault'] == '0' ? ' selected="selected"' : '') . '>0</option>
                            <option value="1"' . ($_SESSION['InvoiceQuantityDefault'] == '1' ? ' selected="selected"' : '') . '>' . __('Outstanding') . '</option>
                        </select>
                    </field>';

                // Pack Note
                echo '<field>
                        <label for="X_DefaultBlindPackNote">' . __('Show Company Details on Packing Slip') . ':</label>
                        <select name="X_DefaultBlindPackNote">
                            <option value="1"' . ($_SESSION['DefaultBlindPackNote'] == '1' ? ' selected="selected"' : '') . '>' . __('Show') . '</option>
                            <option value="2"' . ($_SESSION['DefaultBlindPackNote'] == '2' ? ' selected="selected"' : '') . '>' . __('Hide') . '</option>
                        </select>
                    </field>';

                // Codes
                echo '<field>
                        <label for="X_AutoDebtorNo">' . __('Auto Create Customer Codes') . ':</label>
                        <select name="X_AutoDebtorNo">
                            <option ' . ($_SESSION['AutoDebtorNo']==0 ? 'selected="selected"':'') . ' value="0">' . __('Manual') . '</option>
                            <option ' . ($_SESSION['AutoDebtorNo']==1 ? 'selected="selected"':'') . ' value="1">' . __('Automatic') . '</option>
                        </select>
                    </field>';
                echo '<field>
                        <label for="X_AutoSupplierNo">' . __('Auto Create Supplier Codes') . ':</label>
                        <select name="X_AutoSupplierNo">
                            <option ' . ($_SESSION['AutoSupplierNo']==0 ? 'selected="selected"':'') . ' value="0">' . __('Manual') . '</option>
                            <option ' . ($_SESSION['AutoSupplierNo']==1 ? 'selected="selected"':'') . ' value="1">' . __('Automatic') . '</option>
                        </select>
                    </field>';

                // Tax Category
                $SQL = "SELECT taxcatid, taxcatname FROM taxcategories ORDER BY taxcatname";
                $Result = DB_query($SQL);
                echo '<field>
                        <label for="X_DefaultTaxCategory">' . __('Default Tax Category') . ':</label>
                        <select name="X_DefaultTaxCategory">';
                while($Row = DB_fetch_array($Result)) {
                    echo '<option '.($_SESSION['DefaultTaxCategory'] == $Row['taxcatid']?'selected="selected" ':'').'value="'.$Row['taxcatid'].'">'.$Row['taxcatname'].'</option>';
                }
                echo '</select></field>';

                // Tax Authority name
                echo '<field>
                        <fieldhelp>' . __('e.g. VAT Reg No, GST No, etc.') . '</fieldhelp>
                    </field>';

                // Invoicing & Shipping Advanced
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Shipping & Logistics') . '</h4>
                        <field>
                            <label for="X_WorkingDaysWeek">' . __('Working Days per Week') . ':</label>
                            <select name="X_WorkingDaysWeek">';
                            for($i=1; $i<=7; $i++) echo '<option ' . ($_SESSION['WorkingDaysWeek']==$i ?'selected':'') . ' value="'.$i.'">'.$i.'</option>';
                echo '      </select></field>
                        <field>
                            <label for="X_DispatchCutOffTime">' . __('Dispatch Cut-Off Time') . ':</label>
                            <select name="X_DispatchCutOffTime">';
                            for($i=1; $i<=24; $i++) echo '<option ' . ($_SESSION['DispatchCutOffTime']==$i ?'selected':'') . ' value="'.$i.'">'.$i.':00</option>';
                echo '      </select></field>
                        <field>
                            <label for="X_RequirePickingNote">' . __('Picking Note Required for Delivery') . ':</label>
                            <select name="X_RequirePickingNote">
                                <option value="1"' . ($_SESSION['RequirePickingNote']=='1' ? 'selected':'') . '>' . __('Yes') . '</option>
                                <option value="0"' . ($_SESSION['RequirePickingNote']=='0' ? 'selected':'') . '>' . __('No') . '</option>
                            </select>
                        </field>
                        <field>
                            <label for="X_DoFreightCalc">' . __('Automated Freight Calculation') . ':</label>
                            <select name="X_DoFreightCalc">
                                <option value="1"' . ($_SESSION['DoFreightCalc'] ? 'selected':'') . '>' . __('Enabled') . '</option>
                                <option value="0"' . (!$_SESSION['DoFreightCalc'] ? 'selected':'') . '>' . __('Disabled') . '</option>
                            </select>
                        </field>
                        <field>
                            <label for="X_FreightChargeAppliesIfLessThan">' . __('Apply Freight if Order Below') . ':</label>
                            <input type="text" class="number" name="X_FreightChargeAppliesIfLessThan" value="' . $_SESSION['FreightChargeAppliesIfLessThan'] . '" />
                        </field>
                        <field>
                            <label for="X_Default_Shipper">' . __('Default Courier/Shipper') . ':</label>
                            <select name="X_Default_Shipper">';
                            $shSQL = "SELECT shipper_id, shippername FROM shippers";
                            $shRes = DB_query($shSQL);
                            while($shRow = DB_fetch_array($shRes)) echo '<option ' . ($_SESSION['Default_Shipper']==$shRow['shipper_id']?'selected':'') . ' value="'.$shRow['shipper_id'].'">'.$shRow['shippername'].'</option>';
                echo '      </select></field>
                        <field>
                            <label for="X_CountryOfOperation">' . __('Country of Operation') . ':</label>
                            <select name="X_CountryOfOperation">';
                            foreach($CountriesArray as $cCode => $cName) echo '<option ' . ($_SESSION['CountryOfOperation']==$cCode?'selected':'') . ' value="'.$cCode.'">'.$cName.'</option>';
                echo '      </select></field>
                        <field>
                            <label for="X_AllowSalesOfZeroCostItems">' . __('Allow Sales of Zero Cost Items') . ':</label>
                            <select name="X_AllowSalesOfZeroCostItems">
                                <option value="1"' . ($_SESSION['AllowSalesOfZeroCostItems'] ? 'selected':'') . '>' . __('Yes') . '</option>
                                <option value="0"' . (!$_SESSION['AllowSalesOfZeroCostItems'] ? 'selected':'') . '>' . __('No') . '</option>
                            </select>
                        </field>
                    </div>';

                // Default Price List
                $SQL = "SELECT typeabbrev, sales_type FROM salestypes ORDER BY sales_type";
                $Result = DB_query($SQL);
                echo '<field>
                        <label for="X_DefaultPriceList">' . __('Default Price List') . ':</label>
                        <select name="X_DefaultPriceList">';
                while($Row = DB_fetch_array($Result)) {
                    echo '<option '.($_SESSION['DefaultPriceList'] == $Row['typeabbrev']?'selected="selected" ':'').'value="'.$Row['typeabbrev'].'">'.$Row['sales_type'].'</option>';
                }
                echo '</select></field>';

echo '          <div class="next-btn">
                    <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'accounting\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                    <button type="button" class="architect-btn" onclick="switchTab(\'purchasing\')">' . __('Next: Purchasing & QMS') . ' <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
      </div>';

/* Panel 4: Purchasing */
echo '<!-- Panel 4: Purchasing -->
      <div id="panel-purchasing" class="tab-panel">
        <div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-truck-loading"></i> ' . __('Purchasing & Quality Systems') . '</h3></div>
            <div class="db-card-body">';

                // Multiple PO items
                echo '<field>
                        <label for="X_PO_AllowSameItemMultipleTimes">' . __('Allow Same Item Multiple Times on PO') . ':</label>
                        <select name="X_PO_AllowSameItemMultipleTimes">
                            <option value="1"' . ($_SESSION['PO_AllowSameItemMultipleTimes'] ? ' selected="selected"' : '') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['PO_AllowSameItemMultipleTimes'] ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                    </field>';

                // Authorise
                echo '<field>
                        <label for="X_AutoAuthorisePO">' . __('Auto Authorise PO if within limits') . ':</label>
                        <select name="X_AutoAuthorisePO">
                            <option value="1"' . ($_SESSION['AutoAuthorisePO'] ? ' selected="selected" ':'') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['AutoAuthorisePO'] ? ' selected="selected" ':'') . '>' . __('No') . '</option>
                        </select>
                    </field>';

                // GRN Value
                echo '<field>
                        <label for="X_ShowValueOnGRN">' . __('Show Purchase Values on GRN') . ':</label>
                        <select name="X_ShowValueOnGRN">
                            <option value="1"' . ($_SESSION['ShowValueOnGRN'] ? ' selected="selected"' : '') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['ShowValueOnGRN'] ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                    </field>';

                // Variations
                echo '<field>
                        <label for="X_Check_Qty_Charged_vs_Del_Qty">' . __('Check Qty Charged vs Delivered') . ':</label>
                        <select name="X_Check_Qty_Charged_vs_Del_Qty">
                            <option value="1"' . ($_SESSION['Check_Qty_Charged_vs_Del_Qty'] ? ' selected="selected"' : '') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['Check_Qty_Charged_vs_Del_Qty'] ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                    </field>';
                echo '<field>
                        <label for="X_Check_Price_Charged_vs_Order_Price">' . __('Check Price Charged vs Ordered') . ':</label>
                        <select name="X_Check_Price_Charged_vs_Order_Price">
                            <option value="1"' . ($_SESSION['Check_Price_Charged_vs_Order_Price'] ? ' selected="selected"' : '') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['Check_Price_Charged_vs_Order_Price'] ? ' selected="selected"' : '') . '>' . __('No') . '</option>
                        </select>
                    </field>';
                echo '<field>
                        <label for="X_OverChargeProportion">' . __('Allowed Price Variance %') . ':</label>
                        <input type="text" class="integer" name="X_OverChargeProportion" value="' . $_SESSION['OverChargeProportion'] . '" />
                    </field>';
                echo '<field>
                        <label for="X_OverReceiveProportion">' . __('Allowed Receipt Variance %') . ':</label>
                        <input type="text" class="integer" name="X_OverReceiveProportion" value="' . $_SESSION['OverReceiveProportion'] . '" />
                    </field>';

                // Quality Management
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Quality Control (QMS)') . '</h4>';
                    echo '<field>
                            <label for="X_QualityLogSamples">' . __('Auto Log Samples on Receipt') . ':</label>
                            <select name="X_QualityLogSamples">';
                    if ($_SESSION['QualityLogSamples'] == 0){
                        echo '<option selected="selected" value="0">' . __('No') . '</option><option value="1">' . __('Yes') . '</option>';
                    } else {
                        echo '<option selected="selected" value="1">' . __('Yes') . '</option><option value="0">' . __('No') . '</option>';
                    }
                    echo '</select></field>';
                    echo '<field><label for="X_QualityProdSpecText">' . __('Product Spec Narrative') . ':</label><textarea name="X_QualityProdSpecText">' . $_SESSION['QualityProdSpecText'] . '</textarea></field>';
                    echo '<field><label for="X_QualityCOAText">' . __('Product Certification Narrative') . ':</label><textarea name="X_QualityCOAText">' . $_SESSION['QualityCOAText'] . '</textarea></field>';
                echo '</div>';

                // Emails
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Internal Alerts') . '</h4>';
                    echo '<field><label for="X_FactoryManagerEmail">' . __('Factory Manager Email') . ':</label><input type="email" name="X_FactoryManagerEmail" value="' . $_SESSION['FactoryManagerEmail'] . '" /></field>';
                    echo '<field><label for="X_PurchasingManagerEmail">' . __('Purchasing Manager Email') . ':</label><input type="email" name="X_PurchasingManagerEmail" value="' . $_SESSION['PurchasingManagerEmail'] . '" /></field>';
                    echo '<field><label for="X_InventoryManagerEmail">' . __('Inventory Manager Email') . ':</label><input type="email" name="X_InventoryManagerEmail" value="' . $_SESSION['InventoryManagerEmail'] . '" /></field>';
                echo '</div>';

echo '          <div class="next-btn">
                    <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'sales\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                    <button type="button" class="architect-btn" onclick="switchTab(\'inventory\')">' . __('Next: Inventory & WO') . ' <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
      </div>';

/* Panel 5: Inventory */
echo '<!-- Panel 5: Inventory -->
      <div id="panel-inventory" class="tab-panel">
        <div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-boxes"></i> ' . __('Inventory Control & Manufacturing') . '</h3></div>
            <div class="db-card-body">';

                // Costing
                echo '<field>
                        <label for="X_WeightedAverageCosting">' . __('Inventory Costing Method') . ':</label>
                        <select name="X_WeightedAverageCosting">
                            <option ' . ($_SESSION['WeightedAverageCosting']==1 ? 'selected="selected"':'') . ' value="1">' . __('Weighted Average Costing') . '</option>
                            <option ' . ($_SESSION['WeightedAverageCosting']==0 ? 'selected="selected"':'') . ' value="0">' . __('Standard Costing') . '</option>
                        </select>
                    </field>';
                echo '<field>
                        <label for="X_StandardCostDecimalPlaces">' . __('Cost Decimal Places') . ':</label>
                        <select name="X_StandardCostDecimalPlaces">';
                for ($i=0; $i <= 4; $i++) echo '<option ' . ($_SESSION['StandardCostDecimalPlaces']==$i ? 'selected="selected"':'') . ' value="' . $i . '">' . $i . '</option>';
                echo '</select></field>';

                // Behavior
                echo '<field>
                        <label for="X_ProhibitNegativeStock">' . __('Prohibit Negative Stock') . ':</label>
                        <select name="X_ProhibitNegativeStock">';
                if ($_SESSION['ProhibitNegativeStock']==0) {
                    echo '<option selected="selected" value="0">' . __('No') . '</option><option value="1">' . __('Yes') . '</option>';
                } else {
                    echo '<option selected="selected" value="1">' . __('Yes') . '</option><option value="0">' . __('No') . '</option>';
                }
                echo '</select></field>';

                // Work Orders
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Manufacturing (Auto WO)') . '</h4>';
                    echo '<field>
                            <label for="X_AutoCreateWOs">' . __('Auto Create Work Orders on Shortage') . ':</label>
                            <select name="X_AutoCreateWOs">';
                    if ($_SESSION['AutoCreateWOs']==0) {
                        echo '<option selected="selected" value="0">' . __('No') . '</option><option value="1">' . __('Yes') . '</option>';
                    } else {
                        echo '<option selected="selected" value="1">' . __('Yes') . '</option><option value="0">' . __('No') . '</option>';
                    }
                    echo '</select></field>';
                    echo '</select></field>';
                    
                    echo '<field><label for="X_AutoIssue">' . __('Auto Issue Components') . ':</label><select name="X_AutoIssue">';
                    if ($_SESSION['AutoIssue']==0) {
                        echo '<option selected="selected" value="0">' . __('No') . '</option><option value="1">' . __('Yes') . '</option>';
                    } else {
                        echo '<option selected="selected" value="1">' . __('Yes') . '</option><option value="0">' . __('No') . '</option>';
                    }
                    echo '</select></field>';

                    echo '<field><label for="X_DefineControlledOnWOEntry">' . __('Define Serials at WO Entry') . ':</label><select name="X_DefineControlledOnWOEntry">
                                <option value="1"' . ($_SESSION['DefineControlledOnWOEntry'] ? 'selected':'') . '>' . __('Yes') . '</option>
                                <option value="0"' . (!$_SESSION['DefineControlledOnWOEntry'] ? 'selected':'') . '>' . __('No') . '</option>
                          </select></field>';
                    
                    $SQL = "SELECT loccode,locationname FROM locations";
                    $Result = DB_query($SQL);
                    echo '<field><label for="X_DefaultFactoryLocation">' . __('Default Factory Location') . ':</label><select name="X_DefaultFactoryLocation">';
                    while ($Row = DB_fetch_array($Result)){
                        echo '<option ' . ($_SESSION['DefaultFactoryLocation']==$Row['loccode'] ? 'selected="selected"':'') . ' value="' . $Row['loccode'] . '">' . $Row['locationname'] . '</option>';
                    }
                    echo '</select></field>';
                echo '</div>';

                // Stock Usage & Serials
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Stock Usage & Serialized Items') . '</h4>
                        <field>
                            <label for="X_NumberOfPeriodsOfStockUsage">' . __('Periods of Stock Usage for Graph') . ':</label>
                            <input type="text" class="integer" name="X_NumberOfPeriodsOfStockUsage" value="' . $_SESSION['NumberOfPeriodsOfStockUsage'] . '" />
                        </field>
                        <field>
                            <label for="X_StockUsageShowZeroWithinPeriodRange">' . __('Show Zero Periods in Graphs') . ':</label>
                            <select name="X_StockUsageShowZeroWithinPeriodRange">
                                <option value="1"' . ($_SESSION['StockUsageShowZeroWithinPeriodRange'] ? 'selected':'') . '>' . __('Yes') . '</option>
                                <option value="0"' . (!$_SESSION['StockUsageShowZeroWithinPeriodRange'] ? 'selected':'') . '>' . __('No') . '</option>
                            </select>
                        </field>
                        <field>
                            <label for="X_MaxSerialItemsIssued">' . __('Max Serial Numbers per Receipt') . ':</label>
                            <input type="text" class="integer" name="X_MaxSerialItemsIssued" value="' . $_SESSION['MaxSerialItemsIssued'] . '" />
                        </field>
                    </div>';

                // Storage
                echo '<div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Asset & Storage Paths') . '</h4>';
                    echo '<field><label for="X_part_pics_dir">' . __('Product Images Directory') . ':</label><input type="text" name="X_part_pics_dir" placeholder="e.g. part_pics" value="' . basename($_SESSION['part_pics_dir']) . '" /><fieldhelp>' . __('Folder name inside your company directory.') . '</fieldhelp></field>';
                    echo '<field><label for="X_reports_dir">' . __('Report PDF Directory') . ':</label><input type="text" name="X_reports_dir" placeholder="e.g. reports" value="' . basename($_SESSION['reports_dir']) . '" /><fieldhelp>' . __('Folder name for generated PDF files.') . '</fieldhelp></field>';
                    echo '<field><label for="X_MaxImageSize">' . __('Max Image Size (KB)') . ':</label><input type="text" name="X_MaxImageSize" value="' . $_SESSION['MaxImageSize'] . '" /></field>';
                echo '</div>';

echo '          <div class="next-btn">
                    <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'purchasing\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                    <button type="button" class="architect-btn" onclick="switchTab(\'system\')">' . __('Next: System & Integration') . ' <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
      </div>';

/* Panel 6: System */
echo '<!-- Panel 6: System -->
      <div id="panel-system" class="tab-panel">
        <div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-server"></i> ' . __('System Connectivity & Integration') . '</h3></div>
            <div class="db-card-body">';

                // Connectivity
                echo '<field>
                        <label for="X_SmtpSetting">' . __('Global SMTP Email Setting') . ':</label>
                        <select name="X_SmtpSetting">';
                if ($_SESSION['SmtpSetting'] == 0) echo '<option selected value="0">' . __('Internal Mail') . '</option><option value="1">' . __('Remote SMTP') . '</option>';
                else echo '<option selected value="1">' . __('Remote SMTP') . '</option><option value="0">' . __('Internal Mail') . '</option>';
                echo '</select></field>';

                // Exchange
                echo '<field>
                        <label for="X_UpdateCurrencyRatesDaily">' . __('Auto Update Forex Rates') . ':</label>
                        <select name="X_UpdateCurrencyRatesDaily">
                            <option ' . ($_SESSION['UpdateCurrencyRatesDaily'] != '1' ? 'selected' : '') . ' value="1">' . __('Automatic') . '</option>
                            <option ' . ($_SESSION['UpdateCurrencyRatesDaily'] == '0' ? 'selected' : '') . ' value="0">' . __('Manual') . '</option>
                        </select>
                    </field>';
                echo '<field>
                        <label for="X_DB_Maintenance">' . __('DB Maintenance Interval') . ':</label>
                        <select name="X_DB_Maintenance">';
                        $mOpts = ['1'=>__('Daily'), '7'=>__('Weekly'), '30'=>__('Monthly'), '0'=>__('Never'), '-1'=>__('SysAdmin Only')];
                        foreach($mOpts as $k=>$v) echo '<option ' . ($_SESSION['DB_Maintenance']==$k ? 'selected':'') . ' value="'.$k.'">'.$v.'</option>';
                echo '  </select></field>
                    <field>
                        <label for="X_HTTPS_Only">' . __('Force HTTPS Only') . ':</label>
                        <select name="X_HTTPS_Only">
                            <option value="1"' . ($_SESSION['HTTPS_Only'] ? 'selected':'') . '>' . __('Yes') . '</option>
                            <option value="0"' . (!$_SESSION['HTTPS_Only'] ? 'selected':'') . '>' . __('No') . '</option>
                        </select>
                    </field>
                    <div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;">' . __('Wiki & Knowledgebase Integration') . '</h4>
                        <field>
                            <label for="X_WikiApp">' . __('Wiki Application Type') . ':</label>
                            <select name="X_WikiApp">';
                            $wApps = [__('Disabled'), __('WackoWiki'), __('MediaWiki'), __('DokuWiki')];
                            foreach($wApps as $w) echo '<option ' . ($_SESSION['WikiApp']==$w ?'selected':'') . ' value="'.$w.'">'.$w.'</option>';
                echo '      </select></field>
                        <field>
                            <label for="X_WikiPath">' . __('Full Path or URL to Wiki') . ':</label>
                            <input type="text" name="text" name="X_WikiPath" value="' . $_SESSION['WikiPath'] . '" />
                        </field>
                    </div>';
?>

                    <!-- Missing Integration Fields in System Panel -->
                    <div style="margin-top: 40px; border-top: 2px solid #f3f4f6; padding-top: 30px;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; font-weight: 850; color: #065f46; margin-bottom: 25px;"><?php echo __('Advanced UI & Integration'); ?></h4>
                        <field>
                            <label for="X_geocode_integration"><?php echo __('Geocode Customers and Suppliers'); ?>:</label>
                            <select name="X_geocode_integration">
                                <option <?php echo ($_SESSION['geocode_integration']==1 ? 'selected':''); ?> value="1"><?php echo __('Enabled'); ?></option>
                                <option <?php echo ($_SESSION['geocode_integration']==0 ? 'selected':''); ?> value="0"><?php echo __('Disabled'); ?></option>
                            </select>
                        </field>
                        <field>
                            <label for="X_Extended_CustomerInfo"><?php echo __('Extended Customer Information'); ?>:</label>
                            <select name="X_Extended_CustomerInfo">
                                <option <?php echo ($_SESSION['Extended_CustomerInfo']==1 ? 'selected':''); ?> value="1"><?php echo __('Enabled'); ?></option>
                                <option <?php echo ($_SESSION['Extended_CustomerInfo']==0 ? 'selected':''); ?> value="0"><?php echo __('Disabled'); ?></option>
                            </select>
                        </field>
                        <field>
                            <label for="X_Extended_SupplierInfo"><?php echo __('Extended Supplier Information'); ?>:</label>
                            <select name="X_Extended_SupplierInfo">
                                <option <?php echo ($_SESSION['Extended_SupplierInfo']==1 ? 'selected':''); ?> value="1"><?php echo __('Enabled'); ?></option>
                                <option <?php echo ($_SESSION['Extended_SupplierInfo']==0 ? 'selected':''); ?> value="0"><?php echo __('Disabled'); ?></option>
                            </select>
                        </field>
                        <field>
                            <label for="X_ShortcutMenu"><?php echo __('Allow Short-cut Menus'); ?>:</label>
                            <select name="X_ShortcutMenu">
                                <option <?php echo ($_SESSION['ShortcutMenu']==1 ? 'selected':''); ?> value="1"><?php echo __('Yes'); ?></option>
                                <option <?php echo ($_SESSION['ShortcutMenu']==0 ? 'selected':''); ?> value="0"><?php echo __('No'); ?></option>
                            </select>
                        </field>
                        <field>
                            <label for="X_GoogleTranslatorAPIKey"><?php echo __('Google Translator API Key'); ?>:</label>
                            <input type="text" name="X_GoogleTranslatorAPIKey" value="<?php echo $_SESSION['GoogleTranslatorAPIKey']; ?>" />
                        </field>
                    </div>

                    <div class="next-btn">
                        <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab('inventory')"><i class="fas fa-arrow-left"></i> <?php echo __('Back'); ?></button>
                        <button type="submit" name="submit" class="architect-btn">
                            <i class="fas fa-save"></i> <?php echo __('Complete Core Setup'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</form>
</div>
<?php
include(__DIR__ . '/includes/footer.php');
?>
