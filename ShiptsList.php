<?php

// Shows a list of all the open shipments for a selected supplier. Linked from POItems.php

require(__DIR__ . '/includes/session.php');

$Title = __('Shipments Open Inquiry');
$ViewTopic = 'Shipments';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Security & Sanitization
$SupplierID = isset($_GET['SupplierID']) ? $_GET['SupplierID'] : '';
$SupplierName = isset($_GET['SupplierName']) ? $_GET['SupplierName'] : '';

if (empty($SupplierID) || empty($SupplierName)) {
	echo '<div class="db-page">
			<div class="db-page-header">
				<h1 class="db-page-title">' . __('Error') . '</h1>
			</div>
			<div class="card-v2" style="padding: var(--space-8); text-align: center;">
				<div style="width: 64px; height: 64px; border-radius: 50%; background: var(--danger-soft); color: var(--danger); display: inline-flex; align-items: center; justify-content: center; margin-bottom: var(--space-4);">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				</div>
				<p style="font-size: 1.125rem; font-weight: 600; color: var(--text-main);">' . __('Missing Supplier Information') . '</p>
				<p style="color: var(--text-muted); margin-top: 8px;">' . __('This page must be given the supplier code to look for shipments.') . '</p>
				<div style="margin-top: var(--space-6);">
					<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-primary">' . __('Go Back to Search') . '</a>
				</div>
			</div>
		</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

$SQL = "SELECT shiptref,
			vessel,
			eta
		FROM shipments
		WHERE supplierid='" . DB_escape_string($SupplierID) . "'
		AND closed = 0
		ORDER BY eta ASC";

$ErrMsg = __('No shipments were returned from the database because') . ' - ' . DB_error_msg();
$ShiptsResult = DB_query($SQL, $ErrMsg);

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-row">
				<div class="db-header-main">
					<h1 class="db-page-title">' . __('Open Shipments') . '</h1>
					<p class="db-page-subtitle">' . __('Inquiry for') . ' <span class="val-bold" style="color:var(--primary);">' . htmlspecialchars($SupplierID) . ' - ' . htmlspecialchars($SupplierName) . '</span></p>
				</div>
				<div class="db-header-actions">
					<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-secondary">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						' . __('Change Supplier') . '
					</a>
					<a href="' . $RootPath . '/Shipments.php?NewShipment=Yes" class="db-btn db-btn-primary">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M12 5v14M5 12h14"></path></svg>
						' . __('New Shipment') . '
					</a>
				</div>
			</div>
		</div>';

if (DB_num_rows($ShiptsResult) == 0) {
	echo '<div class="card-v2" style="padding: var(--space-10); text-align: center; background: var(--surface);">
			<div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: var(--space-6);">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
			</div>
			<h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">' . __('No Open Shipments Found') . '</h2>
			<p style="color: var(--text-muted); max-width: 400px; margin: 12px auto 0; line-height: 1.6;">' . __('There are currently no open shipments recorded for') . ' ' . htmlspecialchars($SupplierName) . '. ' . __('You can create a new shipment using the button above.') . '</p>
		</div>';
} else {
	echo '<div class="card-v2" style="overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--border-soft);">
			<div class="card-header-v2" style="padding: var(--space-5) var(--space-6); background: var(--surface); display: flex; align-items: center; gap: 12px; border-bottom: 2px solid var(--border-soft);">
				<div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center;">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
				</div>
				<h2 style="font-size: 1.125rem; font-weight: 700; color: var(--text-main); margin: 0;">' . __('Shipment Listing') . ' <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500; margin-left: 8px;">(' . DB_num_rows($ShiptsResult) . ' ' . __('results') . ')</span></h2>
			</div>
			<div class="db-table-wrapper" style="background: var(--surface);">
				<table class="db-table" style="border-collapse: separate; border-spacing: 0;">
					<thead>
						<tr style="background: var(--surface-alt);">
							<th style="padding: 16px 24px; border-bottom: 2px solid var(--border-soft);">' . __('Reference') . '</th>
							<th style="padding: 16px 24px; border-bottom: 2px solid var(--border-soft);">' . __('Vessel') . '</th>
							<th style="padding: 16px 24px; border-bottom: 2px solid var(--border-soft);">' . __('Estimated Arrival (ETA)') . '</th>
							<th class="noPrint" style="text-align: center; padding: 16px 24px; border-bottom: 2px solid var(--border-soft);">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

	while ($MyRow = DB_fetch_array($ShiptsResult)) {
		echo '<tr>
				<td style="padding: 16px 24px; font-weight: 700; font-size: 0.9375rem;">
					<a href="' . $RootPath . '/Shipments.php?SelectedShipment=' . $MyRow['shiptref'] . '" style="color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="opacity: 0.6;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 1-7.54-.54l-3 3a5 5 0 0 1 7.07 7.07l1.71-1.71"></path></svg>
						' . htmlspecialchars($MyRow['shiptref']) . '
					</a>
				</td>
				<td style="padding: 16px 24px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<div style="width: 32px; height: 32px; border-radius: 6px; background: var(--surface-alt); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
						</div>
						<span style="font-weight: 600; color: var(--text-main);">' . htmlspecialchars($MyRow['vessel']) . '</span>
					</div>
				</td>
				<td style="padding: 16px 24px;">
					<div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.875rem;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity: 0.7;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
						<span style="font-weight: 600; color: var(--text-main);">' . ConvertSQLDate($MyRow['eta']) . '</span>
					</div>
				</td>
				<td class="noPrint" style="padding: 16px 24px; text-align: center;">
					<a href="' . $RootPath . '/Shipments.php?SelectedShipment=' . $MyRow['shiptref'] . '" class="db-btn db-btn-secondary" style="height: 32px; font-size: 0.75rem; border-radius: 8px;">
						' . __('Manage') . '
					</a>
				</td>
			</tr>';
	}

	echo '</tbody></table></div></div>';
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
