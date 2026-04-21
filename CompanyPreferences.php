<?php

// Defines the settings applicable for the company, including name, address, tax authority reference, whether GL integration used etc.

require(__DIR__ . '/includes/session.php');

$ViewTopic = 'CreatingNewSystem';
$BookMark = 'CompanyParameters';
$Title = __('Company Preferences');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'CompanyParameters';

// Inject premium styles for the Architect workspace
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 30px; padding: 0 10px; }
	
	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-sm);
		overflow: hidden;
        height: auto;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 24px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
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
	.architect-btn i { color: #ffffff !important; }

    .db-form-label {
        font-size: 0.75rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 1.2px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 10px;
    }
    .db-input {
        width: 100%; border-radius: 8px; height: 50px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 20px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
    }
    .db-input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }
    
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 280px 1fr; 
        gap: 40px; 
        align-items: start; 
    }

    /* Tab Menu Styles */
    .tab-menu { display: flex; flex-direction: column; gap: 8px; }
    .tab-item {
        display: flex; align-items: center; gap: 16px; padding: 18px 24px;
        background: transparent; border-radius: 10px; border: none;
        color: #4b5563; font-weight: 700; font-size: 0.9rem; text-align: left;
        cursor: pointer; transition: all 0.25s ease;
    }
    .tab-item:hover { background: #f0fdf4; color: #059669; }
    .tab-item.active { background: #059669; color: #ffffff; box-shadow: 0 10px 20px rgba(5, 150, 105, 0.15); }
    .tab-item i { font-size: 1.1rem; width: 24px; text-align: center; }

    .tab-item.locked { opacity: 0.5; pointer-events: none; filter: grayscale(1); }
    .tab-item.completed i.status-icon { color: #059669; }
    .tab-item .status-icon { margin-left: auto; font-size: 0.8rem; opacity: 0.6; }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: slideUp 0.4s ease-out; }

    @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .card-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
    .field-help { font-size: 0.8rem; color: #6b7280; margin-top: 8px; font-weight: 500; display: block; line-height: 1.5; }

    .next-btn {
        margin-top: 40px; display: flex; justify-content: flex-end; gap: 16px;
        padding-top: 30px; border-top: 1px solid #f3f4f6;
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .tab-menu { flex-direction: column; gap: 12px; margin-bottom: 30px; }
        .tab-item { width: 100%; white-space: normal; }
    }
    
    @media (max-width: 768px) {
        .db-page { padding: var(--space-2) var(--space-1); }
        .db-card-body { padding: 25px 20px; }
        .architect-btn { width: 100%; justify-content: center; }
        .next-btn { flex-direction: column; gap: 12px; }
        .tab-item { padding: 14px 20px; font-size: 0.85rem; }
    }
</style>
<script>
    const tabRequirements = {
        \'identity\': [\'CoyName\'],
        \'contact\': [\'RegOffice1\', \'Email\', \'Telephone\'],
        \'finance\': [\'CurrencyDefault\', \'RetainedEarnings\'],
        \'accounts\': [\'DebtorsAct\', \'CreditorsAct\'],
        \'variances\': [\'SalesExchangeDiffAct\', \'PurchasesExchangeDiffAct\']
    };

    const tabOrder = [\'identity\', \'contact\', \'finance\', \'accounts\', \'variances\', \'workflow\'];

    function isTabComplete(tabId) {
        const fields = tabRequirements[tabId];
        if (!fields) return true; // workflow has no requirements
        return fields.every(fieldName => {
            const el = document.querySelector(\'[name="\' + fieldName + \'"]\');
            return el && el.value.trim() !== \'\';
        });
    }

    function updateWizardState() {
        let allPreviousCompleted = true;
        tabOrder.forEach((tabId, index) => {
            const tabItem = document.getElementById(\'tab-\' + tabId);
            const statusIcon = tabItem.querySelector(\'.status-icon\');
            
            // Completion Status
            if (isTabComplete(tabId)) {
                tabItem.classList.add(\'completed\');
                statusIcon.className = \'fas fa-check-circle status-icon\';
            } else {
                tabItem.classList.remove(\'completed\');
                statusIcon.className = \'far fa-circle status-icon\';
            }

            // Locking Logic
            if (index === 0 || allPreviousCompleted) {
                tabItem.classList.remove(\'locked\');
            } else {
                tabItem.classList.add(\'locked\');
            }

            if (!isTabComplete(tabId)) {
                allPreviousCompleted = false;
            }
        });
    }

    function switchTab(tabId) {
        const tabIdx = tabOrder.indexOf(tabId);
        const prevTabId = tabOrder[tabIdx - 1];
        
        // Prevent skipping if locked
        if (tabIdx > 0 && !isTabComplete(prevTabId)) {
            alert(\'Please complete the current section first.\');
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
        document.querySelectorAll(\'.db-input\').forEach(input => {
            input.addEventListener(\'input\', updateWizardState);
            input.addEventListener(\'change\', updateWizardState);
        });
    });
</script>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/UIGeneralFunctions.php');

// Helper for modernized GL Selects
function ModernGLSelect($Name, $Value, $Filter, $Label, $Help) {
    if ($Filter == 'ALL') { $Where = ''; } 
    elseif ($Filter == 'BS') { $Where = 'WHERE accountgroups.pandl=0'; } 
    elseif ($Filter == 'P&L') { $Where = 'WHERE accountgroups.pandl=1'; } 
    else { $Where = ''; }

    $SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster
            INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname
            INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode
                AND glaccountusers.userid='" . $_SESSION['UserID'] . "' " . $Where . "
            ORDER BY chartmaster.accountcode";
    $Result = DB_query($SQL);
    
    $HTML = '<div><label class="db-form-label">' . $Label . '</label><select name="' . $Name . '" class="db-input">';
    $HTML .= '<option value="">' . __('Not Yet Selected') . '</option>';
    while ($Row = DB_fetch_array($Result)) {
        $Selected = ($Row['accountcode'] == $Value) ? 'selected="selected"' : '';
        $HTML .= '<option ' . $Selected . ' value="' . $Row['accountcode'] . '">' . $Row['accountcode'] . ' - ' . htmlspecialchars($Row['accountname'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $HTML .= '</select><span class="field-help">' . $Help . '</span></div>';
    return $HTML;
}

// initialise no input errors assumed initially before we test
$InputError = 0;
$Errors = array();
$i = 1;


if (isset($_POST['submit'])) {


	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (mb_strlen($_POST['CoyName']) > 50 OR mb_strlen($_POST['CoyName'])==0) {
		$InputError = 1;
		prnMsg(__('The company name must be entered and be fifty characters or less long'), 'error');
		$Errors[$i] = 'CoyName';
		$i++;
	}

	if (mb_strlen($_POST['Email'])>0 and !IsEmailAddress($_POST['Email'])) {
		$InputError = 1;
		prnMsg(__('The email address is not correctly formed'),'error');
		$Errors[$i] = 'Email';
		$i++;
	}

	if ($InputError != 1) {

		$CompanyFileHandler = fopen($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/Companies.php', 'w');
		$Contents = "<?php\n\n";
		$Contents.= "\$CompanyName['" . $_SESSION['DatabaseName'] . "'] = '" . $_POST['CoyName'] . "';\n";
		$Contents.= "?>";

		if (!fwrite($CompanyFileHandler, $Contents)) {
			fclose($CompanyFileHandler);
			echo '<div class="error">' . __('Cannot write to the Companies.php file') . '</div>';
		}
		//close file
		fclose($CompanyFileHandler);

		$SQL = "UPDATE companies SET coyname='" . $_POST['CoyName'] . "',
									companynumber = '" . $_POST['CompanyNumber'] . "',
									gstno='" . $_POST['GSTNo'] . "',
									regoffice1='" . $_POST['RegOffice1'] . "',
									regoffice2='" . $_POST['RegOffice2'] . "',
									regoffice3='" . $_POST['RegOffice3'] . "',
									regoffice4='" . $_POST['RegOffice4'] . "',
									regoffice5='" . $_POST['RegOffice5'] . "',
									regoffice6='" . $_POST['RegOffice6'] . "',
									telephone='" . $_POST['Telephone'] . "',
									fax='" . $_POST['Fax'] . "',
									email='" . $_POST['Email'] . "',
									currencydefault='" . $_POST['CurrencyDefault'] . "',
									debtorsact='" . $_POST['DebtorsAct'] . "',
									pytdiscountact='" . $_POST['PytDiscountAct'] . "',
									creditorsact='" . $_POST['CreditorsAct'] . "',
									payrollact='" . $_POST['PayrollAct'] . "',
									grnact='" . $_POST['GRNAct'] . "',
									commissionsact='" . $_POST['CommAct'] . "',
									salesexchangediffact='" . $_POST['SalesExchangeDiffAct'] . "',
									purchasesexchangediffact='" . $_POST['PurchasesExchangeDiffAct'] . "',
									currencyexchangediffact='" . $_POST['CurrencyExchangeDiffAct'] . "',
									unrealizedcurrencydiffact='" . $_POST['UnrealizedCurrencyDiffAct'] . "',
									retainedearnings='" . $_POST['RetainedEarnings'] . "',
									gllink_debtors='" . $_POST['GLLink_Debtors'] . "',
									gllink_creditors='" . $_POST['GLLink_Creditors'] . "',
									gllink_stock='" . $_POST['GLLink_Stock'] ."',
									freightact='" . $_POST['FreightAct'] . "'
								WHERE coycode=1";

			$ErrMsg =  __('The company preferences could not be updated because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg( __('Company preferences updated'),'success');

			/* Alter the exchange rates in the currencies table */

			/* Get default currency rate */
			$SQL="SELECT rate from currencies WHERE currabrev='" . $_POST['CurrencyDefault'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			$NewCurrencyRate=$MyRow[0];

			/* Set new rates */
			$SQL="UPDATE currencies SET rate=rate/" . $NewCurrencyRate;
			$ErrMsg =  __('Could not update the currency rates');
			$Result = DB_query($SQL, $ErrMsg);

			/* End of update currencies */

			$ForceConfigReload = true; // Required to force a load even if stored in the session vars
			include(__DIR__ . '/includes/GetConfig.php');
			$ForceConfigReload = false;

	} else {
		prnMsg( __('Validation failed') . ', ' . __('no updates or deletes took place'),'warn');
	}

} /* end of if submit */

/* Render Layout */
echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: center;">
				<div>
					<div style="font-size: 0.75rem; font-weight: 800; color: #6b7280; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.6;">
						<i class="fas fa-home"></i> ' . __('Setup') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem;"></i> ' . __('Company Profile') . '
					</div>
					<h1 style="font-size: 2.2rem; font-weight: 950; letter-spacing: -1.5px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
				</div>
                <div>
                     <button type="submit" form="main-form" name="submit" class="architect-btn">
                        <i class="fas fa-cloud-upload-alt"></i> ' . __('Save All Preferences') . '
                    </button>
                </div>
			</div>
		</div>';

echo '<form id="main-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
        
        <div class="db-bottom-layout">
            <aside class="db-sidebar">
                <nav class="tab-menu">
                    <button type="button" id="tab-identity" class="tab-item active" onclick="switchTab(\'identity\')">
                        <i class="fas fa-id-badge"></i> ' . __('Company Identity') . '
                        <i class="far fa-circle status-icon"></i>
                    </button>
                    <button type="button" id="tab-contact" class="tab-item" onclick="switchTab(\'contact\')">
                        <i class="fas fa-map-marked-alt"></i> ' . __('Contact Details') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-finance" class="tab-item" onclick="switchTab(\'finance\')">
                        <i class="fas fa-coins"></i> ' . __('Finance Defaults') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-accounts" class="tab-item" onclick="switchTab(\'accounts\')">
                        <i class="fas fa-project-diagram"></i> ' . __('Control Accounts') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-variances" class="tab-item" onclick="switchTab(\'variances\')">
                        <i class="fas fa-balance-scale"></i> ' . __('Exchange & Variations') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                    <button type="button" id="tab-workflow" class="tab-item" onclick="switchTab(\'workflow\')">
                        <i class="fas fa-network-wired"></i> ' . __('GL Integrity Workflow') . '
                        <i class="fas fa-lock status-icon"></i>
                    </button>
                </nav>
                
                <div style="margin-top: 40px; padding: 24px; background: #f0fdf4; border-radius: 20px; border: 1px solid #d1fae5;">
                    <h4 style="font-size: 0.75rem; color: #065f46; font-weight: 900; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">' . __('System Guide') . '</h4>
                    <p style="font-size: 0.85rem; color: #065f46; font-weight: 600; line-height: 1.5; margin: 0;">' . __('Configure these base parameters carefully as they define how the system records global transactions.') . '</p>
                </div>
            </aside>

            <main class="db-main">
                <!-- Panel 1: Identity -->
                <div id="panel-identity" class="tab-panel active">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-building"></i> ' . __('Company Identity & Legal') . '</h3></div>
                        <div class="db-card-body">
                            <div class="card-grid-2">
                                <div style="grid-column: span 2;">
                                    <label class="db-form-label">' . __('Full Business Name') . '</label>
                                    <input type="text" name="CoyName" required="required" maxlength="50" value="' . htmlspecialchars($_POST['CoyName'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                    <span class="field-help">' . __('Enter the name as it should appear on invoices and reports.') . '</span>
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('Official Business Number') . '</label>
                                    <input type="text" name="CompanyNumber" maxlength="20" value="' . htmlspecialchars($_POST['CompanyNumber'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('Tax Reference / TIN') . '</label>
                                    <input type="text" name="GSTNo" maxlength="20" value="' . htmlspecialchars($_POST['GSTNo'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                            </div>
                            <div class="next-btn">
                                <button type="button" class="architect-btn" onclick="switchTab(\'contact\')">' . __('Next: Contact Details') . ' <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: Contact -->
                <div id="panel-contact" class="tab-panel">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-envelope-open-text"></i> ' . __('Registered Office & Presence') . '</h3></div>
                        <div class="db-card-body">
                            <div class="card-grid-2">
                                <div style="grid-column: span 2;">
                                    <label class="db-form-label">' . __('Registered Address') . '</label>
                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                        <input type="text" name="RegOffice1" value="' . htmlspecialchars($_POST['RegOffice1'], ENT_QUOTES, 'UTF-8') . '" class="db-input" placeholder="' . __('Street / Building') . '" />
                                        <input type="text" name="RegOffice2" value="' . htmlspecialchars($_POST['RegOffice2'], ENT_QUOTES, 'UTF-8') . '" class="db-input" placeholder="' . __('Area / District') . '" />
                                        <input type="text" name="RegOffice3" value="' . htmlspecialchars($_POST['RegOffice3'], ENT_QUOTES, 'UTF-8') . '" class="db-input" placeholder="' . __('Additional Info') . '" />
                                    </div>
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('City') . '</label>
                                    <input type="text" name="RegOffice4" value="' . htmlspecialchars($_POST['RegOffice4'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('State / Region') . '</label>
                                    <input type="text" name="RegOffice5" value="' . htmlspecialchars($_POST['RegOffice5'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('Postal Code') . '</label>
                                    <input type="text" name="RegOffice6" value="' . htmlspecialchars($_POST['RegOffice6'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('Official Email') . '</label>
                                    <input type="email" name="Email" value="' . htmlspecialchars($_POST['Email'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('Main Telephone') . '</label>
                                    <input type="tel" name="Telephone" value="' . htmlspecialchars($_POST['Telephone'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('Official Fax') . '</label>
                                    <input type="text" name="Fax" value="' . htmlspecialchars($_POST['Fax'], ENT_QUOTES, 'UTF-8') . '" class="db-input" />
                                </div>
                            </div>
                            <div class="next-btn">
                                <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'identity\')"><i class="fas fa-arrow-left"></i> ' . __('Previous') . '</button>
                                <button type="button" class="architect-btn" onclick="switchTab(\'finance\')">' . __('Next: Finance Defaults') . ' <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 3: Finance -->
                <div id="panel-finance" class="tab-panel">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-piggy-bank"></i> ' . __('Financial Baseline') . '</h3></div>
                        <div class="db-card-body">
                            <div class="card-grid-2">';
                                $SQL = "SELECT currabrev, currency FROM currencies ORDER BY currency";
                                $currResult = DB_query($SQL);
                                echo '<div><label class="db-form-label">' . __('Base Reporting Currency') . '</label><select name="CurrencyDefault" class="db-input">';
                                while ($currRow = DB_fetch_array($currResult)) {
                                    $selected = ($currRow['currabrev'] == $_POST['CurrencyDefault']) ? 'selected="selected"' : '';
                                    echo '<option ' . $selected . ' value="' . $currRow['currabrev'] . '">' . $currRow['currency'] . '</option>';
                                }
                                echo '</select><span class="field-help">' . __('The main currency for financial statements.') . '</span></div>';
                                
                                echo ModernGLSelect('RetainedEarnings', $_POST['RetainedEarnings'], 'BS', __('Retained Earnings (Clearance)'), __('Accumulated profits from previous years.'));
                                echo ModernGLSelect('FreightAct', $_POST['FreightAct'], 'P&L', __('Freight Integration Act'), __('Account for freight recoveries/charges.'));
echo '                      </div>
                            <div class="next-btn">
                                <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'contact\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                                <button type="button" class="architect-btn" onclick="switchTab(\'accounts\')">' . __('Next: Control Accounts') . ' <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 4: Accounts -->
                <div id="panel-accounts" class="tab-panel">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-network-wired"></i> ' . __('Control Ledger Accounts') . '</h3></div>
                        <div class="db-card-body">
                            <div class="card-grid-2">';
                                echo ModernGLSelect('DebtorsAct', $_POST['DebtorsAct'], 'BS', __('Debtors Control'), __('Accounts Receivable master account.'));
                                echo ModernGLSelect('CreditorsAct', $_POST['CreditorsAct'], 'BS', __('Creditors Control'), __('Accounts Payable master account.'));
                                echo ModernGLSelect('PayrollAct', $_POST['PayrollAct'], 'BS', __('Payroll Net Clearance'), __('Temporary account for payroll liabilities.'));
                                echo ModernGLSelect('GRNAct', $_POST['GRNAct'], 'BS', __('Goods Received (GRN) Suspense'), __('Suspense for un-invoiced goods received.'));
                                echo ModernGLSelect('CommAct', $_POST['CommAct'], 'BS', __('Commissions Accrual'), __('Accrued liabilities for sales commissions.'));
echo '                      </div>
                            <div class="next-btn">
                                <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'finance\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                                <button type="button" class="architect-btn" onclick="switchTab(\'variances\')">' . __('Next: Variances') . ' <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 5: Variances -->
                <div id="panel-variances" class="tab-panel">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-chart-line"></i> ' . __('Exchange & Variations') . '</h3></div>
                        <div class="db-card-body">
                            <div class="card-grid-2">';
                                echo ModernGLSelect('SalesExchangeDiffAct', $_POST['SalesExchangeDiffAct'], 'P&L', __('Sales Exchange Diff'), __('Forex gains/losses on customer payments.'));
                                echo ModernGLSelect('PurchasesExchangeDiffAct', $_POST['PurchasesExchangeDiffAct'], 'P&L', __('Purchases Exchange Diff'), __('Forex gains/losses on supplier payments.'));
                                echo ModernGLSelect('CurrencyExchangeDiffAct', $_POST['CurrencyExchangeDiffAct'], 'P&L', __('Cash/Bank Currency Diff'), __('Forex gains/losses on multi-currency transfers.'));
                                echo ModernGLSelect('UnrealizedCurrencyDiffAct', $_POST['UnrealizedCurrencyDiffAct'], 'BS', __('Unrealized Forex P&L'), __('Year-end revaluation differences.'));
                                echo ModernGLSelect('PytDiscountAct', $_POST['PytDiscountAct'], 'P&L', __('Settlement Discounts'), __('Customer discounts for early payment.'));
echo '                      </div>
                            <div class="next-btn">
                                <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'accounts\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                                <button type="button" class="architect-btn" onclick="switchTab(\'workflow\')">' . __('Next: Workflow') . ' <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel 6: Workflow -->
                <div id="panel-workflow" class="tab-panel">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-cog"></i> ' . __('GL Integrity Workflow') . '</h3></div>
                        <div class="db-card-body">
                            <div class="card-grid-2">
                                <div>
                                    <label class="db-form-label">' . __('AR Integration Mode') . '</label>
                                    <select name="GLLink_Debtors" class="db-input">
                                        <option value="0" ' . ($_POST['GLLink_Debtors'] == 0 ? 'selected' : '') . '>' . __('Disconnected (Manual Ledger)') . '</option>
                                        <option value="1" ' . ($_POST['GLLink_Debtors'] == 1 ? 'selected' : '') . '>' . __('Integrated (Real-time Posting)') . '</option>
                                    </select>
                                    <span class="field-help">' . __('Post customer transactions to GL immediately.') . '</span>
                                </div>
                                <div>
                                    <label class="db-form-label">' . __('AP Integration Mode') . '</label>
                                    <select name="GLLink_Creditors" class="db-input">
                                        <option value="0" ' . ($_POST['GLLink_Creditors'] == 0 ? 'selected' : '') . '>' . __('Disconnected (Manual Ledger)') . '</option>
                                        <option value="1" ' . ($_POST['GLLink_Creditors'] == 1 ? 'selected' : '') . '>' . __('Integrated (Real-time Posting)') . '</option>
                                    </select>
                                    <span class="field-help">' . __('Post supplier transactions to GL immediately.') . '</span>
                                </div>
                                <div style="grid-column: span 2;">
                                    <label class="db-form-label">' . __('Inventory Integration Mode') . '</label>
                                    <select name="GLLink_Stock" class="db-input">
                                        <option value="0" ' . ($_POST['GLLink_Stock'] == 0 ? 'selected' : '') . '>' . __('Passive Tracking') . '</option>
                                        <option value="1" ' . ($_POST['GLLink_Stock'] == 1 ? 'selected' : '') . '>' . __('Asset Tracking (GL Integrated)') . '</option>
                                    </select>
                                    <span class="field-help">' . __('Automatic journals for stock moves, adjustments, and COGS.') . '</span>
                                </div>
                            </div>
                            <div class="next-btn">
                                <button type="button" style="background: #f3f4f6; color: #4b5563;" class="architect-btn" onclick="switchTab(\'variances\')"><i class="fas fa-arrow-left"></i> ' . __('Back') . '</button>
                                <button type="submit" name="submit" class="architect-btn">
                                    <i class="fas fa-save"></i> ' . __('Finish & Save All Settings') . '
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </form>
</div>';


include(__DIR__ . '/includes/footer.php');
