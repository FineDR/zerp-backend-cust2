<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Historical Test Results');
$ViewTopic = 'QualityAssurance';
$BookMark = 'QA_HistoricalResults';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (isset($_GET['KeyValue'])){
	$KeyValue =mb_strtoupper($_GET['KeyValue']);
} elseif (isset($_POST['KeyValue'])){
	$KeyValue =mb_strtoupper($_POST['KeyValue']);
} else {
	$KeyValue='';
}

if (!isset($_POST['FromDate'])){
	$_POST['FromDate']=date(($_SESSION['DefaultDateFormat']), mktime(0, 0, 0, date('m'), date('d')-180, date('Y')));
}
if (!isset($_POST['ToDate'])){
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}
if (!Is_Date($_POST['FromDate'])) {
	$InputError = 1;
	prnMsg(__('Invalid From Date'),'error');
	$_POST['FromDate']=date(($_SESSION['DefaultDateFormat']), mktime(0, 0, 0, date('m'), date('d')-180, date('Y')));
}
if (!Is_Date($_POST['ToDate'])) {
	$InputError = 1;
	prnMsg(__('Invalid To Date'),'error');
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}
$FromDate = FormatDateForSQL($_POST['FromDate']);
$ToDate = FormatDateForSQL($_POST['ToDate']);

$Errors = array();

    echo '<style>
        :root {
            --db-primary: hsl(197, 92%, 47%);
            --db-primary-hover: hsl(197, 92%, 38%);
            --db-primary-dark: hsl(197, 75%, 22%);
            --db-primary-soft: hsl(197, 65%, 95%);
            --db-bg: hsl(210, 20%, 97%);
            --db-card-bg: #ffffff;
            --db-border: hsl(210, 14%, 89%);
            --db-text-main: hsl(210, 24%, 16%);
            --db-text-muted: hsl(210, 16%, 46%);
            --radius-lg: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        }

        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, -apple-system, sans-serif; color: var(--db-text-main); }
        .db-centered { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .db-page-header { margin-bottom: 2rem; }
        .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .db-page-title { font-size: 2rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; line-height: 1.1; }

        /* Grid System */
        .db-main-grid { display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start; }
        @media (max-width: 1024px) { .db-main-grid { grid-template-columns: 1fr; } }

        /* Cards */
        .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: var(--shadow-sm); overflow: hidden; }
        .db-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
        .db-card-title { font-size: 0.875rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; display: flex; align-items: center; gap: 10px; }
        .db-card-body { padding: 1.5rem; }

        /* Forms */
        .db-field-group { display: flex; flex-direction: column; gap: 1.25rem; }
        .db-field { display: flex; flex-direction: column; gap: 0.5rem; }
        .db-label { font-size: 0.8125rem; font-weight: 700; color: var(--db-primary-dark); }
        .db-input, .db-select { 
            padding: 0.625rem 0.875rem; 
            border-radius: 8px; 
            border: 1px solid var(--db-border); 
            background: #fff; 
            font-size: 0.875rem; 
            transition: all 0.2s; 
            width: 100%;
        }
        .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }

        /* Buttons */
        .db-btn { 
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; 
            border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none;
        }
        .db-btn-primary { background: var(--db-primary); color: white; width: 100%; }
        .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }

        /* Table Styles */
        .db-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 1rem; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 2px solid var(--db-border); }
        .db-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
        .db-table tr:hover td { background: #f8fafc; }
        
        /* Badges */
        .db-badge { padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.02em; }
        .db-badge-success { background: hsl(197, 63%, 92%); color: hsl(197, 63%, 25%); }
        .db-badge-danger { background: hsl(0, 72%, 94%); color: hsl(0, 72%, 35%); }
        .db-badge-info { background: hsl(210, 30%, 92%); color: hsl(210, 30%, 30%); }

        .db-mono { font-family: "JetBrains Mono", monospace; font-size: 0.8125rem; }
    </style>

    <div class="db-page">
        <div class="db-centered">
            <div class="db-page-header">
                <div class="db-breadcrumb">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    ' . __('Quality Assurance / Analytics') . '
                </div>
                <h1 class="db-page-title">' . $Title . '</h1>
            </div>

            <div class="db-main-grid">
                <!-- Main Content: Results -->
                <div class="db-field-group">';

    //show header
    $SQLSpecSelect="SELECT description
                        FROM stockmaster
                        WHERE stockmaster.stockid='" . $KeyValue . "'";

    $ResultSelection=DB_query($SQLSpecSelect);
    $MyRowSelection=DB_fetch_array($ResultSelection);
    
    if ($KeyValue != '') {
        echo '      <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                ' . $KeyValue . ' - ' . htmlspecialchars($MyRowSelection['description']) . '
                            </h3>
                        </div>
                        <div class="db-card-body" style="padding:0;">';

        $SQLTests="SELECT sampleresults.testid,
                            sampledate,
                            sampleresults.sampleid,
                            lotkey,
                            identifier,
                            cert,
                            isinspec,
                            testvalue,
                            name
                        FROM qasamples INNER JOIN sampleresults
                        ON sampleresults.sampleid=qasamples.sampleid
                        INNER JOIN qatests
                        ON qatests.testid=sampleresults.testid
                        LEFT OUTER JOIN stockmaster on stockmaster.stockid=qasamples.prodspeckey
                        WHERE prodspeckey='" . $KeyValue . "'
                        AND sampledate >='" . $FromDate . "'
                        AND sampledate <='" . $ToDate . "'
                        ORDER BY sampledate DESC, sampleid DESC, testid";

        $ResultTests=DB_query($SQLTests);

        if (DB_num_rows($ResultTests) > 0) {
            echo '<table class="db-table">
                    <thead>
                        <tr>
                            <th>' . __('Date') . '</th>
                            <th>' . __('Lot') . '</th>
                            <th>' . __('ID') . '</th>
                            <th>' . __('Test Name') . '</th>
                            <th>' . __('Value') . '</th>
                            <th>' . __('Pass') . '</th>
                            <th>' . __('COA') . '</th>
                        </tr>
                    </thead>
                    <tbody>';

            while ($MyRow=DB_fetch_array($ResultTests)) {
                $statusClass = $MyRow['isinspec'] == 1 ? 'db-badge-success' : 'db-badge-danger';
                $statusText = $MyRow['isinspec'] == 1 ? __('YES') : __('NO');
                $coaClass = $MyRow['cert'] == 1 ? 'db-badge-success' : 'db-badge-info';
                $coaText = $MyRow['cert'] == 1 ? __('YES') : __('NO');

                echo '<tr>
                        <td class="db-mono">' . ConvertSQLDate($MyRow['sampledate']) . '</td>
                        <td class="db-mono">' . htmlspecialchars($MyRow['lotkey']) . '</td>
                        <td>' . htmlspecialchars($MyRow['identifier']) . '</td>
                        <td style="font-weight:600;">' . htmlspecialchars($MyRow['name']) . '</td>
                        <td class="db-mono" style="font-weight:800; color:var(--db-primary-dark);">' . htmlspecialchars($MyRow['testvalue']) . '</td>
                        <td><span class="db-badge ' . $statusClass . '">' . $statusText . '</span></td>
                        <td><span class="db-badge ' . $coaClass . '">' . $coaText . '</span></td>
                    </tr>';
            }
            echo '      </tbody>
                    </table>';
        } else {
            echo '<div style="padding:3rem; text-align:center; color:var(--db-text-muted);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom:1rem; opacity:0.5;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <p style="margin:0; font-weight:600;">' . __('No historical results found for this period') . '</p>
                  </div>';
        }
        echo '      </div>
                    </div>';
    } else {
        echo '<div class="db-card" style="border-style: dashed; background: transparent; opacity: 0.8;">
                <div class="db-card-body" style="padding: 4rem; text-align: center; color: var(--db-text-muted);">
                    <p style="font-size: 1.125rem; font-weight: 500;">' . __('Please select a product and date range to view historical test data.') . '</p>
                </div>
              </div>';
    }

    echo '      </div>

                <!-- Sidebar: Search Criteria -->
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            ' . __('Search Filters') . '
                        </h3>
                    </div>
                    <div class="db-card-body">
                        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                            <div class="db-field-group">
                                <div class="db-field">
                                    <label class="db-label">' . __('Product Specification') . '</label>';
                                    
    $SQLSpecSelect="SELECT DISTINCT(prodspeckey),
                            description
                        FROM qasamples LEFT OUTER JOIN stockmaster
                        ON stockmaster.stockid=qasamples.prodspeckey";
    $ResultSelection=DB_query($SQLSpecSelect);
    
    echo '<select name="KeyValue" class="db-select">';
    while ($MyRowSelection=DB_fetch_array($ResultSelection)){
        $Selected = ($MyRowSelection['prodspeckey'] == $KeyValue) ? 'selected="selected"' : '';
        echo '<option ' . $Selected . ' value="' . $MyRowSelection['prodspeckey'] . '">' . $MyRowSelection['prodspeckey'] . ' - ' . htmlspecialchars($MyRowSelection['description']) . '</option>';
    }
    echo '</select>
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('From Date') . '</label>
                                    <input name="FromDate" type="date" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('To Date') . '</label>
                                    <input name="ToDate" type="date" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
                                </div>
                                <button type="submit" name="PickSpec" class="db-btn db-btn-primary">
                                    ' . __('Refresh Data') . '
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>';

	include(__DIR__ . '/includes/footer.php');
