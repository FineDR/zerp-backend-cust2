<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Payment Terms Maintenance');
$ViewTopic = 'PaymentTerms';
$BookMark = 'PaymentTerms';

// Inject premium Architect Workspace styles
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: 20px 15px; background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; box-sizing: border-box; }
	
	.premium-header { 
        margin: -20px -15px 30px -15px;
        padding: 20px; 
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        max-width: 100%;
        margin: 0 auto;
        gap: 20px;
    }
	
    .breadcrumb-wrap { 
        font-size: 0.65rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; 
        display: flex; align-items: center; gap: 8px; text-transform: uppercase; 
        letter-spacing: 1px; opacity: 0.6;
    }
    .breadcrumb-wrap a { color: inherit; text-decoration: none; }
    .breadcrumb-wrap a:hover { text-decoration: underline; opacity: 1; }

	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-md);
		overflow: hidden;
        margin-bottom: 30px;
        width: 100%;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
	}
	.db-card-title {
		font-size: 0.8rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 25px; }
	
    field {
        display: block;
        margin-bottom: 18px;
    }
    field label {
        font-size: 0.62rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 6px;
        opacity: 0.7;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    .fieldhelp { font-size: 0.75rem; color: #64748b; margin-top: 6px; display: block; font-weight: 500; }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-emerald { background: #d1fae5; color: #065f46; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    .check-wrap { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8fafc; border-radius: 10px; border: 1px solid #edf2f7; margin-bottom: 18px; cursor: pointer; }
    .check-wrap input { width: 18px; height: 18px; cursor: pointer; margin: 0; }
    .check-wrap span { font-size: 0.8rem; font-weight: 700; color: #064e3b; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedTerms'])){
	$SelectedTerms = $_GET['SelectedTerms'];
} elseif (isset($_POST['SelectedTerms'])){
	$SelectedTerms = $_POST['SelectedTerms'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (mb_strlen($_POST['TermsIndicator']) < 1 || mb_strlen($_POST['TermsIndicator']) > 2) {
		$InputError = 1;
		prnMsg(__('The payment terms name must be 1-2 characters long'),'error');
	}
	if (empty($_POST['DayNumber']) || !is_numeric(filter_number_format($_POST['DayNumber']))){
		$InputError = 1;
		prnMsg(__('The number of days must be numeric'),'error');
	}

	if (isset($SelectedTerms) AND $InputError != 1) {
		if (isset($_POST['DaysOrFoll']) AND $_POST['DaysOrFoll']=='on') {
			$SQL = "UPDATE paymentterms SET terms='" . $_POST['Terms'] . "', dayinfollowingmonth=0, daysbeforedue='" . filter_number_format($_POST['DayNumber']) . "' WHERE termsindicator = '" . $SelectedTerms . "'";
		} else {
			$SQL = "UPDATE paymentterms SET terms='" . $_POST['Terms'] . "', dayinfollowingmonth='" . filter_number_format($_POST['DayNumber']) . "', daysbeforedue=0 WHERE termsindicator = '" . $SelectedTerms . "'";
		}
		$Msg = __('Updated payment terms record');
	} elseif ($InputError != 1) {
		if (isset($_POST['DaysOrFoll']) && $_POST['DaysOrFoll']=='on') {
			$SQL = "INSERT INTO paymentterms (termsindicator, terms, daysbeforedue, dayinfollowingmonth) VALUES ('" . $_POST['TermsIndicator'] . "', '" . $_POST['Terms'] . "', '" . filter_number_format($_POST['DayNumber']) . "', 0)";
		} else {
			$SQL = "INSERT INTO paymentterms (termsindicator, terms, daysbeforedue, dayinfollowingmonth) VALUES ('" . $_POST['TermsIndicator'] . "', '" . $_POST['Terms'] . "', 0, '" . filter_number_format($_POST['DayNumber']) . "')";
		}
		$Msg = __('Added new payment terms');
	}

	if ($InputError != 1){
		DB_query($SQL);
		prnMsg($Msg,'success');
		unset($SelectedTerms); unset($_POST['TermsIndicator']); unset($_POST['Terms']); unset($_POST['DayNumber']); unset($_POST['DaysOrFoll']);
	}
} elseif (isset($_GET['delete'])) {
	$CheckCust = DB_query("SELECT COUNT(*) FROM debtorsmaster WHERE paymentterms = '" . $SelectedTerms . "'");
	$CheckSupp = DB_query("SELECT COUNT(*) FROM suppliers WHERE paymentterms = '" . $SelectedTerms . "'");
	if (DB_fetch_row($CheckCust)[0] > 0 || DB_fetch_row($CheckSupp)[0] > 0) {
		prnMsg(__('Cannot delete this term because accounts refer to it'),'warn');
	} else {
		DB_query("DELETE FROM paymentterms WHERE termsindicator='" . $SelectedTerms . "'");
		prnMsg(__('Term deleted'),'success');
	}
	unset($SelectedTerms);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=system">' . __('Setup') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Payment Terms') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="terms-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedTerms) ? __('Update Terms') : __('Create Terms')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                $SQL_list = "SELECT * FROM paymentterms ORDER BY termsindicator";
                $Result_list = DB_query($SQL_list);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-calendar-check"></i> ' . __('Defined Credit Definitions') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Code') . '</th>
                                    <th>' . __('Description') . '</th>
                                    <th>' . __('Type') . '</th>
                                    <th style="text-align:right;">' . __('Value') . '</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result_list)) {
                                $isFollMth = ($MyRow['dayinfollowingmonth'] != 0);
                                echo '<tr>
                                        <td><span class="badge badge-emerald">', $MyRow['termsindicator'], '</span></td>
                                        <td style="font-weight: 600; color: #064e3b;">', $MyRow['terms'], '</td>
                                        <td>', ($isFollMth ? '<span class="badge badge-secondary">' . __('Foll. Month Day') . '</span>' : '<span class="badge badge-secondary">' . __('Net Days') . '</span>'), '</td>
                                        <td style="text-align:right; font-weight:700; color:#059669;">', ($isFollMth ? $MyRow['dayinfollowingmonth'] . 'th' : $MyRow['daysbeforedue'] . ' Days'), '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedTerms=', $MyRow['termsindicator'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedTerms=', $MyRow['termsindicator'], '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedTerms)) {
                    $SQL_sel = "SELECT * FROM paymentterms WHERE termsindicator='" . $SelectedTerms . "'";
                    $MyRow = DB_fetch_array(DB_query($SQL_sel));
                    $_POST['TermsIndicator'] = $MyRow['termsindicator'];
                    $_POST['Terms'] = $MyRow['terms'];
                    $DayValue = ($MyRow['daysbeforedue'] != 0) ? $MyRow['daysbeforedue'] : $MyRow['dayinfollowingmonth'];
                    $isDays = ($MyRow['daysbeforedue'] != 0);
                }

echo '          <form id="terms-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedTerms)) { echo '<input type="hidden" name="SelectedTerms" value="' . $SelectedTerms . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-clock"></i> ' . (isset($SelectedTerms) ? __('Edit Terms') : __('New Terms')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Term Indicator') . '</label>
                                <input type="text" name="TermsIndicator" ' . (isset($SelectedTerms) ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required maxlength="2" autofocus') . ' value="' . ($_POST['TermsIndicator'] ?? '') . '" placeholder="e.g. 30" />
                            </field>
                            <field>
                                <label>' . __('Terms Description') . '</label>
                                <input type="text" name="Terms" required maxlength="40" value="' . ($_POST['Terms'] ?? '') . '" placeholder="e.g. Net 30 Days" />
                            </field>
                            
                            <label class="check-wrap">
                                <input type="checkbox" name="DaysOrFoll" ' . ((isset($isDays) && $isDays) ? 'checked' : '') . ' />
                                <span>' . __('Due After Fixed No. Days') . '</span>
                            </label>
                            
                            <field>
                                <label>' . __('Day Count / Date') . '</label>
                                <input type="number" name="DayNumber" required value="' . ($DayValue ?? '') . '" />
                                <span class="fieldhelp">' . __('Enter days if checked, or day of month if unchecked') . '</span>
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:20px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedTerms) ? __('Update Definition') : __('Save Definition')) . '
                            </button>
                            ' . (isset($SelectedTerms) ? '<div style="text-align:center; margin-top:15px;"><a href="PaymentTerms.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
