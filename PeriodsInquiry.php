<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Periods Inquiry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'PeriodsInquiry';
include(__DIR__ . '/includes/header.php');

// Inject premium Architect styles (Minimalist & Smooth)
echo '<style>
    :root {
        --primary: #059669;
        --primary-dark: #065f46;
        --primary-light: #ecfdf5;
        --border-color: #f1f5f9;
        --text-main: #334155;
    }
    .db-page {
        padding: 20px 30px;
        max-width: 1400px;
        margin: 0 auto;
        font-family: "Inter", sans-serif;
        color: var(--text-main);
    }
    .premium-header { 
        margin-bottom: 24px; 
        padding: 16px 20px; 
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 12px;
    }
    
    .db-bottom-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 24px;
        align-items: start;
    }
    
    /* Sleek Mini-Card */
    .arch-card { 
        background: #ffffff; 
        border-radius: 12px; 
        border: 1px solid #e2e8f0; 
        overflow: hidden;
        margin-bottom: 24px;
    }
    .arch-card-header { 
        background: #f8fafc; 
        border-bottom: 1px solid #f1f5f9; 
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .arch-card-title {
        font-size: 0.8rem; font-weight: 800; color: #1e293b; margin:0;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    /* Smooth Modern Table */
    .smooth-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .smooth-table th {
        text-align: left;
        padding: 12px 20px;
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #f1f5f9;
    }
    .smooth-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 600;
        vertical-align: middle;
    }
    .smooth-table tr:hover {
        background: #f0fdf4;
    }
    .smooth-table tr.active-row {
        background: #ecfdf5;
    }
    .smooth-table tr.active-row td {
        color: var(--primary-dark);
    }
    
    /* Minimalist Side Nav */
    .nav-item {
        padding: 10px 16px; 
        border-bottom: 1px solid #f1f5f9; 
        transition: all 0.2s; 
        display: flex; justify-content: space-between; 
        text-decoration: none; color: #475569;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .nav-item:hover { color: var(--primary); background: #f8fafc; }
    
    .arch-badge { 
        padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.6rem; text-transform: uppercase; 
    }
    .status-past { background: #f1f5f9; color: #64748b; }
    .status-current { background: #dcfce7; color: #166534; }
    .status-future { background: #e0f2fe; color: #0369a1; }

    .action-link {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        color: #64748b; 
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.85rem;
        margin-left: 8px;
        text-decoration: none;
    }
    .action-link:hover { 
        background: var(--primary); 
        color: #fff; 
        transform: scale(1.1);
    }
    .action-link i { color: inherit; }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .db-col-aside { order: 2; }
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
';

$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
$PeriodsResult = DB_query($SQL);

$CurrentDate = date('Y-m-d');
$PeriodsByYear = [];
$AllPeriods = [];
while ($MyRow = DB_fetch_array($PeriodsResult)) {
    $Year = date('Y', strtotime($MyRow['lastdate_in_period']));
    $PeriodsByYear[$Year][] = $MyRow;
    $AllPeriods[] = $MyRow;
}

echo '<div class="db-page">
		<header class="premium-header">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 900; color: #1e293b; margin: 0;">' . $Title . '</h2>
                <p style="font-size: 0.75rem; color: #64748b; margin: 2px 0 0 0; font-weight: 500;">' . __('Grouped by Fiscal Year') . '</p>
            </div>
            <a href="' . $RootPath . '/index.php?Application=GeneralLedger" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> ' . __('Back to Ledger') . '
            </a>
		</header>';

echo '<div class="db-bottom-layout">
        <!-- Slim Nav -->
        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 20px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title">' . __('Fiscal Years') . '</h3>
                </div>
                <div class="db-card-body">';
    
    foreach (array_keys($PeriodsByYear) as $Year) {
        echo '<a href="#year-' . $Year . '" class="nav-item">
                <span>' . $Year . '</span>
                <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.3;"></i>
              </a>';
    }

echo '          </div>
                <div style="padding: 15px; background: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 0.7rem;">
                    <div style="display:flex; justify-content:space-between; font-weight:700;">
                        <span>' . __('Total Periods') . '</span>
                        <span style="color:var(--primary);">' . count($AllPeriods) . '</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Simple Smooth Table -->
        <main class="db-col-main">';

    foreach ($PeriodsByYear as $Year => $YearPeriods) {
        echo '<div class="arch-card" id="year-' . $Year . '">
                <div class="arch-card-header">
                    <h3 class="arch-card-title">' . __('Fiscal Year') . ' ' . $Year . '</h3>
                </div>
                <table class="smooth-table">
                    <thead>
                        <tr>
                            <th>' . __('Period') . '</th>
                            <th>' . __('Last Day of Period') . '</th>
                            <th>' . __('Status') . '</th>
                            <th style="text-align:right;">' . __('Actions') . '</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($YearPeriods as $Period) {
            $LastDate = $Period['lastdate_in_period'];
            $IsCurrent = (date('Y-m', strtotime($LastDate)) == date('Y-m'));
            
            $StatusClass = 'status-past'; $StatusLabel = __('Historic');
            if ($IsCurrent) {
                $StatusClass = 'status-current'; $StatusLabel = __('Active');
            } elseif ($LastDate > $CurrentDate) {
                $StatusClass = 'status-future'; $StatusLabel = __('Future');
            }

            echo '<tr class="' . ($IsCurrent ? 'active-row' : '') . '">
                    <td style="color:#0f172a; font-weight:800;">' . $Period['periodno'] . '</td>
                    <td>' . ConvertSQLDate($LastDate) . '</td>
                    <td><span class="arch-badge ' . $StatusClass . '">' . $StatusLabel . '</span></td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a href="' . $RootPath . '/GLTransInquiry.php?FromPeriod=' . $Period['periodno'] . '&ToPeriod=' . $Period['periodno'] . '" class="action-link" title="' . __('View Transactions') . '">
                            <i class="fas fa-search-dollar"></i>
                        </a>
                        <a href="' . $RootPath . '/GLJournal.php" class="action-link" title="' . __('Post Journal') . '">
                            <i class="fas fa-plus"></i>
                        </a>
                    </td>
                  </tr>';
        }
        
        echo '      </tbody>
                </table>
              </div>';
    }

echo '  </main>
    </div>';

echo '</div>';

include(__DIR__ . '/includes/footer.php');
