<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Serial Item Research');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">
		<form id="SerialNoResearch" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Serial Lookup') . '</h3>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label" for="serialno">' . __('Serial Number') . '</label>
						<input id="serialno" type="text" name="serialno" class="db-input" value="'. $SerialNo . '" placeholder="' . __('e.g. SN12345') . '" autofocus />
					</div>
					<button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
						<i class="fas fa-search"></i> ' . __('Search Now') . '
					</button>
				</div>
			</div>
		</form>
	</aside>';

echo '<main class="db-col-main">';

echo '<script>
		document.getElementById("serialno").focus();
	</script>';



if ($SerialNo!='') {
	//the point here is to allow a semi fuzzy search, but still keep someone from killing the db server
	if (mb_strstr($SerialNo,'%')){
		while(mb_strstr($SerialNo,'%%'))	{
			$SerialNo = str_replace('%%','%',$SerialNo);
		}
		if (mb_strlen($SerialNo) < 11){
			$SerialNo = str_replace('%','',$SerialNo);
			prnMsg('You can not use LIKE with short numbers. It has been removed.','warn');
		}
	}
	$SQL = "SELECT ssi.serialno,
			ssi.stockid, ssi.quantity CurInvQty,
			ssm.moveqty,
			sm.type, st.typename,
			sm.transno, sm.loccode, l.locationname, sm.trandate, sm.debtorno, sm.branchcode, sm.reference, sm.qty TotalMoveQty
			FROM stockserialitems ssi INNER JOIN stockserialmoves ssm
				ON ssi.serialno = ssm.serialno AND ssi.stockid=ssm.stockid
			INNER JOIN stockmoves sm
				ON ssm.stockmoveno = sm.stkmoveno and ssi.loccode=sm.loccode
			INNER JOIN systypes st
				ON sm.type=st.typeid
			INNER JOIN locations l
				on sm.loccode = l.loccode
			INNER JOIN locationusers ON locationusers.loccode=l.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE ssi.serialno " . LIKE . " '" . $SerialNo . "'
			ORDER BY stkmoveno";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0){
		echo '<div class="db-status-bar db-status-warning">
				<div class="db-status-icon"><i class="fas fa-exclamation-triangle"></i></div>
				<div class="db-status-text">' . __('No History found for Serial Number') . ': <b>' . $SerialNo . '</b></div>
			  </div>';
	} else {
		echo '<div class="db-card">
				<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
					<h3 class="db-card-title"><i class="fas fa-history"></i> ' . __('Transaction History') . '</h3>
					<span class="db-badge db-badge-primary">' . $SerialNo . '</span>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Stock Item') . '</th>
									<th class="text-right">' . __('Cur Inv') . '</th>
									<th class="text-right">' . __('Move Qty') . '</th>
									<th>' . __('Move Type') . '</th>
									<th class="text-right">' . __('Trans #') . '</th>
									<th>' . __('Location') . '</th>
									<th>' . __('Date') . '</th>
									<th>' . __('Entity/Ref') . '</th>
									<th class="text-right">' . __('Total Qty') . '</th>
								</tr>
							</thead>
							<tbody>';
		while ($MyRow=DB_fetch_row($Result)) {
			echo '			<tr>
								<td>
									<div class="db-font-bold text-primary">' . $MyRow[1] . '</div>
									<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow[0] . '</div>
								</td>
								<td class="text-right db-font-bold">' . $MyRow[2] . '</td>
								<td class="text-right db-font-bold" style="color: var(--primary);">' . $MyRow[3] . '</td>
								<td>
									<div class="db-font-bold">' . $MyRow[5] . '</div>
									<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow[4] . '</div>
								</td>
								<td class="text-right">' . $MyRow[6] . '</td>
								<td>
									<div class="db-font-bold">' . $MyRow[7] . '</div>
									<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow[8] . '</div>
								</td>
								<td>' . $MyRow[9] . '</td>
								<td>
									<div class="db-font-bold">' . ($MyRow[10] ? $MyRow[10] . ' / ' . $MyRow[11] : '') . '</div>
									<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow[12] . '</div>
								</td>
								<td class="text-right">' . $MyRow[13] . '</td>
							</tr>';
		}
		echo '				</tbody>
						</table>
					</div>
				</div>
			  </div>';
	}
}

echo '	</main>
	</div>'; // end db-bottom-layout


include(__DIR__ . '/includes/footer.php');
