<?php

require(__DIR__ . '/includes/session.php');
$Title = __('General Ledger Export (CSV)');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountCSV';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['Period'])) $SelectedPeriod = $_POST['Period'];
elseif (isset($_GET['Period'])) $SelectedPeriod = $_GET['Period'];

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
</style>';

if (isset($_POST['MakeCSV'])) {
	if (!isset($SelectedPeriod) or !isset($_POST['Account'])) { prnMsg(__('Accounts and periods must be selected'), 'info'); include(__DIR__ . '/includes/footer.php'); exit(); }
	if (!file_exists($_SESSION['reports_dir'])) mkdir('./' . $_SESSION['reports_dir']);
	$FileName = $_SESSION['reports_dir'] . '/GL_Export_' . date('Y-m-d') . '.csv';
	$fp = fopen($FileName, 'w');

	foreach ($_POST['Account'] as $SelectedAccount) {
		$AccDet = DB_fetch_array(DB_query("SELECT chartmaster.accountname, accountgroups.pandl FROM accountgroups INNER JOIN chartmaster ON accountgroups.groupname=chartmaster.group_ WHERE chartmaster.accountcode='" . $SelectedAccount . "'"));
		$PandL = ($AccDet['pandl'] == 1); $F1 = min($SelectedPeriod); $L1 = max($SelectedPeriod);

		$SQL = "SELECT gltrans.type, systypes.typename, gltrans.typeno, gltrans.trandate, gltrans.narrative, gltrans.amount, gltrans.periodno, gltags.tagref AS tag FROM gltrans INNER JOIN systypes ON systypes.typeid=gltrans.type LEFT JOIN gltags ON gltrans.counterindex=gltags.counterindex WHERE gltrans.account = '" . $SelectedAccount . "' AND periodno>='" . $F1 . "' AND periodno<='" . $L1 . "'";
		if (isset($_POST['tag']) and $_POST['tag'] != -1) $SQL .= " AND gltags.tagref='" . $_POST['tag'] . "'";
		$SQL .= " ORDER BY periodno, gltrans.trandate, gltrans.counterindex";
		$Res = DB_query($SQL);

		fwrite($fp, "Account," . $SelectedAccount . " - " . $AccDet['accountname'] . ",Periods," . $F1 . " to " . $L1 . "\n");
		if ($PandL) $RunningTotal = 0;
		else {
			$Bfwd = DB_fetch_array(DB_query("SELECT SUM(amount) AS bfwd FROM gltotals WHERE account = '" . $SelectedAccount . "' AND period < '" . $F1 . "'"))['bfwd'];
			$RunningTotal = $Bfwd;
			fwrite($fp, ",," . __('Brought Forward Balance') . ",,," . ($RunningTotal >= 0 ? $RunningTotal : '') . "," . ($RunningTotal < 0 ? -$RunningTotal : '') . "\n");
		}

		while ($R = DB_fetch_array($Res)) {
			$RunningTotal += $R['amount'];
			$Tag = DB_fetch_array(DB_query("SELECT tagdescription FROM tags WHERE tagref='" . $R['tag'] . "'"))['tagdescription'];
			fwrite($fp, $R['typename'] . "," . $R['typeno'] . "," . ConvertSQLDate($R['trandate']) . ",\"" . $R['narrative'] . "\",\"" . $Tag . "\"," . ($R['amount'] >= 0 ? $R['amount'] : '') . "," . ($R['amount'] < 0 ? -$R['amount'] : '') . "\n");
		}
		fwrite($fp, ",," . __('Final Balance') . ",,," . ($RunningTotal >= 0 ? $RunningTotal : '') . "," . ($RunningTotal < 0 ? -$RunningTotal : '') . "\n\n");
	}
	fclose($fp);

    echo '<div class="db-page"><div class="db-card" style="max-width:500px; margin:0 auto; text-align:center;">
            <div class="db-card-header"><h3 class="db-card-title">Export Complete</h3></div>
            <div class="db-card-body">
                <i class="fas fa-file-csv" style="font-size:3rem; color:var(--db-primary); margin-bottom:1rem;"></i>
                <p style="font-weight:600; color:#475569;">Your General Ledger export is ready.</p>
                <a href="' . $FileName . '" class="db-btn db-btn-primary" style="text-decoration:none;">Download CSV File</a>
                <a href="'.basename(__FILE__).'" class="db-btn" style="text-decoration:none; background:#f1f5f9; color:#475569; margin-top:10px;">Select New Range</a>
            </div>
        </div></div>';

} else {
    echo '<div class="db-page">
            <h1 style="font-size: 2rem; font-weight: 950; letter-spacing: -1.5px; color: var(--db-primary-dark); text-align: center; margin-bottom: 2.5rem;">' . $Title . '</h1>
            <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="db-layout">
                <aside style="display:flex; flex-direction:column; gap:1.5rem;">
                    <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title">Export Options</h3></div>
                        <div class="db-card-body">
                            <button type="submit" name="MakeCSV" class="db-btn db-btn-primary">Generate CSV File</button>
                            <div style="margin-top:1.5rem; padding:1rem; background:var(--db-primary-soft); border:1px solid #d1fae5; border-radius:8px; font-size:0.75rem; color:var(--db-primary-dark);">
                                Use Shift/Ctrl for multi-select.
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
                    <div class="db-card-header"><h3 class="db-card-title">Account & Period Selection</h3></div>
                    <div class="db-card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                        <div class="db-form-group">
                            <label class="db-label">Target Accounts</label>
                            <select name="Account[]" size="15" multiple class="db-select" style="height:400px;">';
                            $SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1 ORDER BY chartmaster.accountcode";
                            $AccRes = DB_query($SQL);
                            while($R = DB_fetch_array($AccRes)) echo '<option value="'.$R['accountcode'].'">'.$R['accountcode'].' '.$R['accountname'].'</option>';
                            echo '</select>
                        </div>
                        <div class="db-form-group">
                            <label class="db-label">Select Periods</label>
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
}

include(__DIR__ . '/includes/footer.php');
?>
