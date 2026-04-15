<?php

/* This script is to review the item description translations. */

require(__DIR__ . '/includes/session.php');

$Title = __('Review Translated Descriptions');
$ViewTopic = 'Inventory';
$BookMark = 'ReviewTranslatedDescriptions';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR: Context & Info
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-language"></i> ' . __('Maintenance') . '</h3>
		</div>
		<div class="db-card-body p-4">
			<div class="db-font-bold text-primary mb-2">' . __('Translation Workbench') . '</div>
			<p class="db-muted mb-4">' . __('Use this interface to review and approve machine or manual translations that have been marked for revision.') . '</p>
			
			<div style="padding-top: 20px; border-top: 1px dashed var(--border-soft);">
				<div class="db-font-medium mb-1" style="font-size: 0.8rem;">' . __('System Tips') . '</div>
				<ul class="db-muted" style="font-size: 0.75rem; padding-left: 15px;">
					<li class="mb-1">' . __('Short descriptions are capped at 50 chars.') . '</li>
					<li class="mb-1">' . __('Check "Approved" to remove items from this queue.') . '</li>
					<li>' . __('Long descriptions support multi-line text.') . '</li>
				</ul>
			</div>
		</div>
	  </div>
	</aside>';

// MAIN: Translation Grid
echo '<main class="db-col-main">';

//update database if update pressed
if (isset($_POST['Submit'])) {
	for ($i=1;$i<count($_POST);$i++) { //loop through the returned translations

		if (isset($_POST['Revised' . $i]) AND ($_POST['Revised' . $i] == '1')) {
			$SQLUpdate="UPDATE stockdescriptiontranslations
						SET needsrevision = '0',
							descriptiontranslation = '". $_POST['DescriptionTranslation' .$i] ."',
							longdescriptiontranslation = '". $_POST['LongDescriptionTranslation' .$i] ."'
						WHERE stockid = '". $_POST['StockID' .$i] ."'
							AND language_id = '". $_POST['LanguageID' .$i] ."'";
			$ResultUpdate = DB_Query($SQLUpdate,'', '', true);
			prnMsg($_POST['StockID' .$i] . ' ' . __('descriptions') . ' ' .  __('in') . ' ' . $_POST['LanguageID' .$i] . ' ' . __('have been updated'),'success');
		}
	}
}

	$SQL = "SELECT stockdescriptiontranslations.stockid,
					stockmaster.description,
					stockmaster.longdescription,
					stockdescriptiontranslations.language_id,
					stockdescriptiontranslations.descriptiontranslation,
					stockdescriptiontranslations.longdescriptiontranslation
			FROM stockdescriptiontranslations, stockmaster
			WHERE stockdescriptiontranslations.stockid = stockmaster.stockid
				AND stockdescriptiontranslations.needsrevision = '1'
			ORDER BY stockdescriptiontranslations.stockid,
					stockdescriptiontranslations.language_id";

	$Result = DB_query($SQL);
	$NumRow = DB_num_rows($Result);

	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-edit"></i> ' . __('Pending Revisions') . '</h3>
				<div class="db-badge db-badge-info">' . $NumRow . ' ' . __('Translations') . '</div>
			</div>
			<div class="db-card-body p-0">';

	if ($NumRow > 0) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div class="db-table-wrapper">
					<table class="db-table table-auto">
						<thead>
							<tr>
								<th>' . __('Item / Language') . '</th>
								<th>' . __('Source Content') . '</th>
								<th>' . __('Target Translation') . '</th>
								<th class="text-center">' . __('Approved?') . '</th>
							</tr>
						</thead>
						<tbody>';

		$i = 1;
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<tr class="db-row-hover">
					<td style="width: 180px; vertical-align: top;">
						<div class="db-font-bold text-primary mb-1">' . $MyRow['stockid'] . '</div>
						<div class="db-badge db-badge-secondary">' . $MyRow['language_id'] . '</div>
					</td>
					<td style="max-width: 400px; vertical-align: top;">
						<div class="db-font-bold mb-1" style="font-size: 0.85rem;">' . htmlspecialchars($MyRow['description']) . '</div>
						<div class="db-muted" style="font-size: 0.75rem; max-height: 80px; overflow-y: auto;">' . nl2br(htmlspecialchars($MyRow['longdescription'])) . '</div>
					</td>
					<td style="vertical-align: top;">
						<div class="mb-2">
							<input class="db-input p-1" style="width: 100%; height: 32px; font-weight: 500;" maxlength="50" name="DescriptionTranslation' . $i . '" type="text" value="' . htmlspecialchars($MyRow['descriptiontranslation']) . '" placeholder="' . __('Short Translation...') . '" />
						</div>
						<div>
							<textarea class="db-input p-2" name="LongDescriptionTranslation' . $i . '" rows="3" style="width: 100%; line-height: 1.4; font-size: 0.8rem;" placeholder="' . __('Long Translation...') . '">' . htmlspecialchars($MyRow['longdescriptiontranslation']) . '</textarea>
						</div>
					</td>
					<td class="text-center" style="width: 100px; vertical-align: middle;">
						<label class="db-label" style="display: block; cursor: pointer;">
							<input name="Revised' . $i . '" type="checkbox" value="1" style="width: 20px; height: 20px;" />
							<input name="StockID' . $i . '" type="hidden" value="' . $MyRow['stockid'] . '" />
							<input name="LanguageID' . $i . '" type="hidden" value="' . $MyRow['language_id'] . '" />
						</label>
					</td>
				  </tr>';
			$i++;
		}

		echo '			</tbody>
					</table>
				</div>
				<div class="db-card-body border-top text-right" style="padding: 15px;">
					<button type="submit" name="Submit" class="db-btn db-btn-primary">
						<i class="fas fa-save mr-2"></i> ' . __('Commit Revisions') . '
					</button>
				</div>
			  </form>';
	} else {
		echo '<div class="text-center db-muted" style="padding: 80px;">
				<div style="width: 70px; height: 70px; background: var(--bg-main); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; opacity: 0.5;">
					<i class="fas fa-check-circle fa-3x"></i>
				</div>
				<h4 class="db-font-bold">' . __('Queue Fully Processed') . '</h4>
				<p>' . __('There are currently no item descriptions marked for revision.') . '</p>
			  </div>';
	}

	echo '</div>
		  </div>';

echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
