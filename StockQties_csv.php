<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Produce Stock Quantities CSV');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

function stripcomma($str) { //because we're using comma as a delimiter
	return str_replace(',', '', $str);
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . __('Inventory') .'" alt="" /><b>' . $Title. '</b></p>';

echo '<div class="centre">' . __('Making a comma separated values file of the current stock quantities') . '</div>';

$ErrMsg = __('The SQL to get the stock quantities failed with the message');

$SQL = "SELECT stockid, SUM(quantity) FROM locstock
			INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			GROUP BY stockid HAVING SUM(quantity)<>0";
$Result = DB_query($SQL, $ErrMsg);

if (!file_exists($_SESSION['reports_dir'])){
	$Result = mkdir('./' . $_SESSION['reports_dir']);
}

$FileName = $_SESSION['reports_dir'] . '/StockQties.csv';

$fp = fopen($FileName,'w');

if ($fp==false){

	prnMsg(__('Could not open or create the file under') . ' ' . $_SESSION['reports_dir'] . '/StockQties.csv','error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

// the BOM is not used much anymore in 2025...
//fputs($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
while ($MyRow = DB_fetch_row($Result)){
	$Line = stripcomma($MyRow[0]) . ', ' . stripcomma($MyRow[1]);
	fputs($fp, $Line . "\n");
}

fclose($fp);

	echo '<style>
		.modern-status-card {
			max-width: 600px;
			margin: 40px auto;
			padding: 40px;
			background: var(--surface);
			border: 1px solid var(--border);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-md);
			text-align: center;
		}
		.status-icon {
			font-size: 3.5rem;
			color: var(--primary);
			margin-bottom: 20px;
			display: block;
		}
		.status-msg {
			font-size: 1.1rem;
			font-weight: 600;
			color: var(--text-main);
			margin-bottom: 10px;
		}
		.status-sub {
			color: var(--text-muted);
			margin-bottom: 30px;
			font-size: 0.9rem;
		}
		.download-btn {
			display: inline-flex;
			align-items: center;
			gap: 10px;
			padding: 12px 28px;
			background: var(--primary);
			color: white;
			text-decoration: none;
			border-radius: var(--radius-sm);
			font-weight: 700;
			transition: all var(--transition-fast);
			box-shadow: 0 4px 12px var(--primary-glow);
		}
		.download-btn:hover {
			background: var(--primary-hover);
			transform: translateY(-2px);
			box-shadow: 0 6px 16px var(--primary-glow);
			color: white;
		}
	</style>';

	echo '<div class="modern-status-card">';
	echo '<span class="status-icon">📥</span>';
	echo '<div class="status-msg">' . __('Stock Quantities CSV Ready') . '</div>';
	echo '<div class="status-sub">' . __('The comma separated values file has been generated successfully.') . '</div>';
	echo '<a href="' . $RootPath . '/' . $_SESSION['reports_dir'] . '/StockQties.csv" class="download-btn">' . __('Download CSV File') . '</a>';
	echo '</div>';

	include(__DIR__ . '/includes/footer.php');
