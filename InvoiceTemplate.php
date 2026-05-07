<?php
/**
 * REUSABLE DYNAMIC INVOICE TEMPLATE
 * Primary Color: #059669 (Green)
 * Used for Browser Preview, PDF, and Print.
 */

if (!function_exists('safe')) {
    function safe($value, $default = 'Not provided') {
        return (isset($value) && trim($value) !== '' && !preg_match('/^address[1-6]$/i', trim($value))) ? $value : $default;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?= safe($invoice['number']) ?></title>
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #f0fdf4;
            --slate-50: #f8fafc;
            --slate-200: #e2e8f0;
            --slate-600: #475569;
            --slate-900: #0f172a;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: #fff;
            color: var(--slate-900);
            line-height: 1.2;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }

        .container {
            max-width: 760px;
            margin: 15px auto;
            padding: 15px;
            background: #fff;
        }

        /* Header Layout */
        .header {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-left {
            display: table-cell;
            vertical-align: top;
            width: 60%;
        }
        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 40%;
        }

        .logo {
            max-height: 50px;
            margin-bottom: 10px;
        }

        .document-title {
            font-size: 28px;
            font-weight: 900;
            color: var(--primary);
            margin: 0;
            letter-spacing: -1px;
        }

        .doc-meta {
            font-size: 13px;
            margin-top: 5px;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 8px;
        }

        /* Sections Layout */
        .details-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .details-col {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding-right: 20px;
        }

        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--slate-600);
            font-weight: 800;
            margin-bottom: 8px;
            display: block;
            letter-spacing: 1px;
        }

        .value {
            font-size: 13px;
            font-weight: 600;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th {
            background: var(--primary-light);
            color: var(--primary);
            text-align: left;
            padding: 12px 15px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 800;
            border-bottom: 2px solid var(--primary);
        }

        th, td {
            padding: 3px 8px;
            text-align: left;
            border-bottom: 1px solid var(--slate-50);
            font-size: 11px;
        }

        .right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Totals Block */
        .summary-wrapper {
            display: table;
            width: 100%;
            margin-top: 15px;
        }
        .summary-left { display: table-cell; width: 60%; vertical-align: bottom; }
        .summary-right { display: table-cell; width: 40%; vertical-align: top; }

        .totals-table {
            width: 100%;
            margin-top: 0;
        }
        .totals-table td {
            padding: 8px 0;
            border-bottom: 1px solid var(--slate-50);
        }
        .total-row td {
            border-top: 2px solid var(--primary);
            color: var(--primary);
            font-size: 15px;
            font-weight: 900;
            padding-top: 5px;
        }

        .footer {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid var(--slate-200);
            font-size: 9px;
            color: var(--slate-600);
            text-align: center;
        }

        .actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .btn-print {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            font-family: inherit;
        }

        .btn-print:hover { background: var(--primary-dark); }

        /* Hide ERP leftovers in screen view for a cleaner standalone document */
        .breadcrumb, .ScriptTitle, .MainBody > h1:first-child, .quick_menu, .main_menu {
            display: none !important;
        }

        @media print {
            @page { margin: 0.8cm; }
            body { 
                margin: 0; 
                padding: 0;
                background: white;
            }
            .actions, .btn-print, .ModuleList, #SidebarToggle, .sidebar-mask, .help-bubble, 
            .header_container, .menu_container, header, footer, .breadcrumb, .no-print,
            #header_container, #menu_container, #footer_container, .quick_menu, .main_menu,
            .ScriptTitle, .MainBody > h1:first-child, #AppIcon, #ActionIcon, #Info, #ExitIcon,
            #help-bubble, #MessageContainerHead, #logoutDialog, #mask { 
                display: none !important; 
            }
            .container { 
                margin: 0; 
                max-width: 100%; 
                width: 100%; 
                padding: 0 5px; 
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-left">
            <?php 
            $logo_path = safe($invoice['logo'], '');
            if (!empty($logo_path) && file_exists($logo_path)): ?>
                <img src="<?= $logo_path ?>" class="logo" alt="Logo">
            <?php endif; ?>
            <div style="font-size: 16px; font-weight: 800;"><?= safe($invoice['company_name']) ?></div>
            <div style="font-size: 12px; color: var(--slate-600); margin-top: 5px;">
                <?= safe($invoice['company_address']) ?><br>
                <?= safe($invoice['company_contact']) ?>
            </div>
        </div>
        <div class="header-right">
            <h1 class="document-title"><?= safe($invoice['title'], 'TAX INVOICE') ?></h1>
            <div class="doc-meta">#<?= safe($invoice['number']) ?></div>
            <div class="status-badge"><?= safe($invoice['status']) ?></div>
            <div style="margin-top: 15px; font-size: 12px;">
                <strong>Date:</strong> <?= safe($invoice['date']) ?><br>
                <strong>Due:</strong> <?= safe($invoice['due_date']) ?>
            </div>
        </div>
    </div>

    <div class="details-grid">
        <div class="details-col">
            <span class="label">Bill To</span>
            <div class="value">
                <strong><?= safe($customer['name']) ?></strong><br>
                <?= safe($customer['address']) ?>
            </div>
        </div>
        <div class="details-col">
            <span class="label">Ship To</span>
            <div class="value"><?= safe($customer['ship_to']) ?></div>
        </div>
        <div class="details-col" style="padding-right:0;">
            <span class="label">Payment Terms</span>
            <div class="value"><?= safe($invoice['terms']) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Code</th>
                <th>Description</th>
                <th class="right" width="10%">Qty</th>
                <th class="right" width="15%">Price</th>
                <th class="right" width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="font-bold"><?= safe($item['code']) ?></td>
                        <td>
                            <div class="font-bold"><?= safe($item['description']) ?></div>
                            <?php if (!empty($item['narrative'])): ?>
                                <div style="font-size: 11px; color: var(--slate-600); margin-top: 5px;">
                                    <?= nl2br($item['narrative']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="right"><?= safe($item['qty']) ?></td>
                        <td class="right"><?= safe($item['price']) ?></td>
                        <td class="right font-bold"><?= safe($item['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" align="center">No items found for this transaction.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-wrapper">
        <div class="summary-left">
            <span class="label">Notes</span>
            <div style="font-size: 12px;"><?= safe($invoice['notes'], 'No additional notes.') ?></div>
        </div>
        <div class="summary-right">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="right value"><?= safe($totals['subtotal']) ?></td>
                </tr>
                <tr>
                    <td class="label">Freight</td>
                    <td class="right value"><?= safe($totals['freight']) ?></td>
                </tr>
                <tr>
                    <td class="label">Tax</td>
                    <td class="right value"><?= safe($totals['tax']) ?></td>
                </tr>
                <?php if (!empty($totals['paid'])): ?>
                    <tr>
                        <td class="label">Amount Paid</td>
                        <td class="right value" style="color: var(--primary);">
                            (<?= safe($totals['paid']) ?>)
                        </td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="right"><?= safe($totals['total']) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <div>Thank you for your business!</div>
        <div style="margin-top: 5px; opacity: 0.7;">
            Printed at: <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>
</div>

<?php if (!isset($_GET['PrintPDF']) || $_GET['PrintPDF'] != 'True'): ?>
<div class="actions no-print">
    <button class="btn-print" onclick="window.print()">
        Download / Print Document
    </button>
</div>
<?php endif; ?>

</body>
</html>
