	$Style = \'
		@page { margin: 15mm; }
		body { font-family: "Helvetica", sans-serif; font-size: 9pt; color: #334155; line-height: 1.4; margin: 0; padding: 0; }
		.logo { max-height: 50px; margin-bottom: 20px; }
		.header-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border-bottom: 2px solid #059669; padding-bottom: 20px; }
		.header-left { width: 60%; vertical-align: top; }
		.header-right { width: 40%; vertical-align: top; text-align: right; }
		.company-name { font-size: 14pt; font-weight: 900; color: #059669; margin-bottom: 4px; }
		.report-title { font-size: 20pt; font-weight: 900; color: #059669; letter-spacing: -0.5px; }
		.meta-label { font-size: 7pt; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.5px; }
		.meta-value { font-size: 9pt; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
		
		.summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 30px; }
		.summary-grid { display: table; width: 100%; }
		.summary-item { display: table-cell; width: 33%; padding: 0 10px; }
		
		table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		th { background: #f1f5f9; color: #475569; font-weight: 800; font-size: 7pt; text-transform: uppercase; text-align: left; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
		td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
		.number { text-align: right; font-weight: 600; }
		.total-row { background: #f8fafc; font-weight: 900; border-top: 2px solid #059669; }
		.footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 10px; }
	\';

	$HTML = \'<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<style>\' . $Style . \'</style>
			</head>
			<body>\';

	$HTML .= \'<table class="header-table">
				<tr>
					<td class="header-left">
						<img class="logo" src="\' . $_SESSION[\'LogoFile\'] . \'" />
						<div class="company-name">\' . $_SESSION[\'CompanyRecord\'][\'coyname\'] . \'</div>
						<div style="font-size: 8pt; color: #64748b;">\' . nl2br($_SESSION[\'CompanyRecord\'][\'regoffice1\']) . \'</div>
					</td>
					<td class="header-right">
						<div class="report-title">\' . __(\'Banking Summary\') . \'</div>
						<div class="meta-label">\' . __(\'Batch Number\') . \'</div>
						<div class="meta-value">#\' . $_POST[\'BatchNo\'] . \'</div>
						<div class="meta-label">\' . __(\'Date of Banking\') . \'</div>
						<div class="meta-value">\' . ConvertSQLDate($BankedDate) . \'</div>
					</td>
				</tr>
			</table>\';

	$HTML .= \'<div class="summary-card">
				<div class="summary-grid">
					<div class="summary-item">
						<div class="meta-label">\' . __(\'Bank Account\') . \'</div>
						<div class="meta-value">\' . $BankActName . \'</div>
					</div>
					<div class="summary-item">
						<div class="meta-label">\' . __(\'Account Number\') . \'</div>
						<div class="meta-value">\' . $BankActNumber . \'</div>
					</div>
					<div class="summary-item" style="text-align: right;">
						<div class="meta-label">\' . __(\'Currency\') . \'</div>
						<div class="meta-value">\' . $Currency . \'</div>
					</div>
				</div>
			</div>\';

	$HTML .= \'<table>
				<thead>
					<tr>
						<th style="width: 15%;">\' . __(\'Amount\') . \'</th>
						<th style="width: 30%;">\' . __(\'Target / Customer\') . \'</th>
						<th style="width: 25%;">\' . __(\'Bank Details\') . \'</th>
						<th style="width: 30%;">\' . __(\'Narrative\') . \'</th>
					</tr>
				</thead>
				<tbody>\';

	$TotalBanked = 0;

	while ($CustRow = DB_fetch_array($CustRecs)) {
		$HTML .= \'<tr>
					<td class="number">\' . locale_number_format(-$CustRow[\'ovamount\'], $BankCurrDecimalPlaces) . \'</td>
					<td style="font-weight: 700; color: #0f172a;">\' . $CustRow[\'name\'] . \'</td>
					<td style="font-size: 8pt;">\' . $CustRow[\'invtext\'] . \'</td>
					<td style="font-size: 8pt; color: #64748b;">\' . $CustRow[\'reference\'] . \'</td>
				</tr>\';
		$TotalBanked -= $CustRow[\'ovamount\'];
	}

	while ($GLRow = DB_fetch_array($GLRecs)){
		$HTML .= \'<tr>
					<td class="number">\' . locale_number_format((-$GLRow[\'amount\']*$ExRate*$FunctionalExRate), $BankCurrDecimalPlaces) . \'</td>
					<td style="font-weight: 700; color: #0f172a;">\' . __(\'General Ledger\') . \'</td>
					<td></td>
					<td style="font-size: 8pt; color: #64748b;">\' . $GLRow[\'narrative\'] . \'</td>
				</tr>\';
		$TotalBanked += (-$GLRow[\'amount\']*$ExRate);
	}

	$HTML .= \'<tr class="total_row">
				<td class="number" style="font-size: 11pt; color: #059669;">\' . locale_number_format($TotalBanked, $BankCurrDecimalPlaces) . \'</td>
				<td colspan="3" style="text-align: left; padding-left: 20px;">\' . __(\'TOTAL\') . \' \' . $Currency . \' \' . __(\'BANKED\') . \'</td>
			</tr>\';

	$HTML .= \'</tbody>
			</table>\';

	$HTML .= \'<div class="footer">
				\' . $_SESSION[\'CompanyRecord\'][\'coyname\'] . \' - \' . __(\'Banking Summary\') . \' #\' . $_POST[\'BatchNo\'] . \' - \' . __(\'Printed\') . \': \' . date($_SESSION[\'DefaultDateFormat\'] . \' H:i\') . \'
			</div>\';

	$HTML .= \'</body></html>\';
