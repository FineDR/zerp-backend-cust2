<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('G/L Multi-Account Report');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountReport';

if (isset($_POST['Period'])) $SelectedPeriod = $_POST['Period'];
elseif (isset($_GET['Period'])) $SelectedPeriod = $_GET['Period'];

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	if (!isset($SelectedPeriod)) { prnMsg(__('Period not selected'), 'info'); include(__DIR__ . '/includes/footer.php'); exit(); }
	if (!isset($_POST['Account'])) { prnMsg(__('Accounts not selected'), 'info'); include(__DIR__ . '/includes/footer.php'); exit(); }

	$HTML = '';
	if (isset($_POST['PrintPDF'])) { $HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>'; }

	$HTML .= '<div class="report-header" style="text-align:center; margin-bottom:2rem;">
                <h1 style="margin:0; color:#1e293b; font-size:1.6rem;">' . $_SESSION['CompanyRecord']['coyname'] . '</h1>
                <div style="font-weight:900; color:hsl(145, 63%, 38%); font-size:1.1rem; text-transform:uppercase;">' . __('General Ledger Activity Report') . '</div>
                <div style="color:#64748b; font-size:0.8rem; margin-top:5px;">Printed: ' . date($_SESSION['DefaultDateFormat']) . ' | User: ' . $_SESSION['UserID'] . '</div>
              </div>';

	foreach ($_POST['Account'] as $SelectedAccount) {
		$AccDet = DB_fetch_array(DB_query("SELECT chartmaster.accountname, accountgroups.pandl FROM accountgroups INNER JOIN chartmaster ON accountgroups.groupname=chartmaster.group_ WHERE chartmaster.accountcode='" . $SelectedAccount . "'"));
		$AccountName = $AccDet['accountname']; $PandL = ($AccDet['pandl'] == 1);
		$F1 = min($SelectedPeriod); $L1 = max($SelectedPeriod);

		$SQL = "SELECT gltrans.counterindex, gltrans.type, typename, gltrans.typeno, gltrans.trandate, gltrans.narrative, gltrans.amount, gltrans.periodno, gltags.tagref AS tag FROM gltrans INNER JOIN systypes ON gltrans.type=systypes.typeid LEFT JOIN gltags ON gltrans.counterindex=gltags.counterindex WHERE gltrans.account = '" . $SelectedAccount . "' AND periodno>='" . $F1 . "' AND periodno<='" . $L1 . "'";
		if (isset($_POST['tag']) and $_POST['tag'] != -1) $SQL .= " AND gltags.tagref='" . $_POST['tag'] . "'";
		$SQL .= " ORDER BY periodno, gltrans.trandate, gltrans.counterindex";
		$TransResult = DB_query($SQL);

		$HTML .= '<div style="margin-top:2rem; padding:10px; background:hsl(145, 40%, 95%); border-radius:8px; border:1px solid #d1fae5; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-weight:900; color:hsl(145, 45%, 22%); font-size:0.95rem;">' . $SelectedAccount . ' - ' . $AccountName . '</div>
                    <div style="font-size:0.75rem; color:#64748b;">Period Range: ' . $F1 . ' to ' . $L1 . '</div>
                  </div>';

		$HTML .= '<table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.8rem; margin-bottom:2rem;">
                    <thead><tr style="background:#475569; color:white; font-size:0.7rem; text-transform:uppercase;">
                        <th>' . __('Type') . '</th><th>' . __('Ref') . '</th><th>' . __('Date') . '</th><th style="text-align:right;">' . __('Debit') . '</th><th style="text-align:right;">' . __('Credit') . '</th><th>' . __('Narrative') . '</th><th>' . __('Tags') . '</th>
                    </tr></thead><tbody>';

		if ($PandL) $RunningTotal = 0;
		else {
			$Bfwd = DB_fetch_array(DB_query("SELECT SUM(amount) AS bfwd FROM gltotals WHERE account = '" . $SelectedAccount . "' AND period < '" . $F1 . "'"))['bfwd'];
			$RunningTotal = $Bfwd;
			$HTML .= '<tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3">' . __('Brought Forward Balance') . '</td>';
			if ($RunningTotal < 0) $HTML .= '<td></td><td style="text-align:right;">' . locale_number_format(-$RunningTotal, 2) . '</td>';
			else $HTML .= '<td style="text-align:right;">' . locale_number_format($RunningTotal, 2) . '</td><td></td>';
			$HTML .= '<td colspan="2"></td></tr>';
		}

		$PeriodTotal = 0; $PeriodNo = -9999;
		while ($R = DB_fetch_array($TransResult)) {
			if ($R['periodno'] != $PeriodNo) {
				if ($PeriodNo != -9999) {
					$HTML .= '<tr style="border-top:1px solid #cbd5e1; font-weight:700;">
                                <td colspan="3">' . __('Period Total') . '</td>';
					if ($PeriodTotal < 0) $HTML .= '<td></td><td style="text-align:right;">' . locale_number_format(-$PeriodTotal, 2) . '</td>';
					else $HTML .= '<td style="text-align:right;">' . locale_number_format($PeriodTotal, 2) . '</td><td></td>';
					$HTML .= '<td colspan="2"></td></tr>';
				}
				$PeriodNo = $R['periodno']; $PeriodTotal = 0;
			}
			$RunningTotal += $R['amount']; $PeriodTotal += $R['amount'];
			$Deb = ($R['amount'] >= 0 ? locale_number_format($R['amount'], 2) : '');
			$Cre = ($R['amount'] < 0 ? locale_number_format(-$R['amount'], 2) : '');

			$HTML .= '<tr style="border-bottom:1px solid #f1f5f9;">
                        <td>' . $R['typename'] . '</td><td style="text-align:right;">' . $R['typeno'] . '</td><td>' . ConvertSQLDate($R['trandate']) . '</td>
                        <td style="text-align:right; color:hsl(145, 63%, 38%);">' . $Deb . '</td><td style="text-align:right; color:#dc2626;">' . $Cre . '</td>
                        <td style="font-size:0.75rem; color:#475569;">' . htmlspecialchars($R['narrative']) . '</td>
                        <td style="font-size:0.7rem; color:#64748b;">' . $R['tag'] . '</td>
                    </tr>';
		}

		$HTML .= '<tr style="background:#e2e8f0; font-weight:900;">
                    <td colspan="3">' . ($PandL ? __('Total Period Movement') : __('Balance Carried Forward')) . '</td>';
		if ($RunningTotal < 0) $HTML .= '<td></td><td style="text-align:right;">' . locale_number_format(-$RunningTotal, 2) . '</td>';
		else $HTML .= '<td style="text-align:right;">' . locale_number_format($RunningTotal, 2) . '</td><td></td>';
		$HTML .= '<td colspan="2"></td></tr></tbody></table>';
	}

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'landscape'); $DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_GL_Report_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	} else {
		$Title = __('General Ledger Account Report'); include(__DIR__ . '/includes/header.php');
		echo '<style>.report-grid { max-width: 1200px; margin: 0 auto; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); } table th, table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }</style>';
		echo '<div style="background:hsl(210, 20%, 97%); padding:2rem; min-height:100vh;"><div class="report-grid">' . $HTML . '</div></div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else {
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --db-primary: hsl(145, 63%, 38%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
        .db-layout { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; max-width: 1300px; margin: 0 auto; }
        .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .db-card-header { padding: 1rem; border-bottom: 1px solid var(--db-border); }
        .db-card-title { font-size: 0.8rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin:0; }
        .db-card-body { padding: 1.5rem; }
        .db-form-group { margin-bottom: 1.25rem; }
        .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
        .db-select { padding: 0.5rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.85rem; width: 100%; }
        .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; width: 100%; }
        .db-btn-primary { background: var(--db-primary); color: #fff; }
        .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); margin-top: 10px; }
    </style>';

    echo '<div class="db-page">
            <h1 style="font-size: 2rem; font-weight: 950; letter-spacing: -1.5px; color: var(--db-primary-dark); text-align: center; margin-bottom: 2.5rem;">' . $Title . '</h1>
            <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" target="_blank">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="db-layout">
                <aside style="display:flex; flex-direction:column; gap:1.5rem;">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title">Run Report</h3></div>
                        <div class="db-card-body">
                            <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">Generate Official PDF</button>
                            <button type="submit" name="View" class="db-btn db-btn-ghost">View Online Inquiry</button>
                            <div style="margin-top:1.5rem; padding:1rem; background:#fefce8; border:1px solid #fde68a; border-radius:8px; font-size:0.75rem; color:#854d0e;">
                                <b>Tip:</b> Hold Ctrl / Cmd to select multiple accounts and periods.
                            </div>
                        </div>
                    </div>
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title">Filter by Tag</h3></div>
                        <div class="db-card-body">
                            <select name="tag" class="db-select">
                                <option value="-1">All Tags</option>';
                                $Tags = DB_query("SELECT tagref, tagdescription FROM tags ORDER BY tagref");
                                while($R = DB_fetch_array($Tags)) echo '<option value="'.$R['tagref'].'">'.$R['tagref'].' - '.$R['tagdescription'].'</option>';
                                echo '</select>
                        </div>
                    </div>
                </aside>
                <main class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title">Criteria Selection</h3></div>
                    <div class="db-card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                        <div class="db-form-group">
                            <label class="db-label">Select Accounts</label>
                            <select name="Account[]" size="15" multiple class="db-select" style="height:400px;">';
                            $SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1 ORDER BY chartmaster.accountcode";
                            $AccRes = DB_query($SQL);
                            while($R = DB_fetch_array($AccRes)) echo '<option value="'.$R['accountcode'].'">'.$R['accountcode'].' '.$R['accountname'].'</option>';
                            echo '</select>
                        </div>
                        <div class="db-form-group">
                            <label class="db-label">Select Fiscal Periods</label>
                            <select name="Period[]" size="15" multiple class="db-select" style="height:400px;">';
                            $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                            while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                            echo '</select>
                        </div>
                    </div>
                </main>
            </div>
            </form>
          </div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
