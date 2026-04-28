<?php

require(__DIR__ . '/includes/session.php');
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['LotKey']))  {
	$SelectedCOA=$_GET['LotKey'];
} elseif (isset($_POST['LotKey'])) {
	$SelectedCOA=$_POST['LotKey'];
}
if (isset($_GET['ProdSpec']))  {
	$SelectedSpec=$_GET['ProdSpec'];
} elseif (isset($_POST['ProdSpec'])) {
	$SelectedSpec=$_POST['ProdSpec'];
}

if (isset($_GET['QASampleID']))  {
	$QASampleID=$_GET['QASampleID'];
} elseif (isset($_POST['QASampleID'])) {
	$QASampleID=$_POST['QASampleID'];
}

//Get Out if we have no Certificate of Analysis
if ((!isset($SelectedCOA) || $SelectedCOA=='') && (!isset($QASampleID) || $QASampleID=='')){
	$ViewTopic = 'QualityAssurance';
	$BookMark = '';
	$Title = __('Select Certificate of Analysis To Print');
	include(__DIR__ . '/includes/header.php');

    echo '<style>
        :root {
            --db-primary: hsl(145, 63%, 38%);
            --db-primary-hover: hsl(145, 63%, 32%);
            --db-primary-dark: hsl(145, 45%, 22%);
            --db-primary-soft: hsl(145, 40%, 95%);
            --db-bg: hsl(210, 20%, 97%);
            --db-card-bg: #ffffff;
            --db-border: hsl(210, 14%, 89%);
            --db-text-main: hsl(210, 24%, 16%);
            --db-text-muted: hsl(210, 16%, 46%);
            --radius-lg: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        }

        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, -apple-system, sans-serif; color: var(--db-text-main); }
        .db-centered { max-width: 800px; margin: 0 auto; }
        
        /* Header */
        .db-page-header { margin-bottom: 2rem; }
        .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .db-page-title { font-size: 2rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; line-height: 1.1; }

        /* Cards */
        .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.5rem; }
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
            display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.75rem 1.5rem; 
            border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%;
        }
        .db-btn-primary { background: var(--db-primary); color: white; }
        .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }

        .db-divider { display: flex; align-items: center; gap: 1rem; color: var(--db-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin: 1.5rem 0; }
        .db-divider::before, .db-divider::after { content: ""; flex: 1; height: 1px; background: var(--db-border); }
    </style>

    <div class="db-page">
        <div class="db-centered">
            <div class="db-page-header">
                <div class="db-breadcrumb">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    ' . __('Quality Assurance / COA') . '
                </div>
                <h1 class="db-page-title">' . __('Certificate of Analysis') . '</h1>
            </div>

            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        ' . __('Select Lot for COA Generation') . '
                    </h3>
                </div>
                <div class="db-card-body">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        
                        <div class="db-field-group">
                            <div class="db-field">
                                <label class="db-label">' . __('Select Existing Valid Lot') . '</label>';
                                
    $SQLSpecSelect="SELECT sampleid,
                            lotkey,
                            prodspeckey,
                            description
                        FROM qasamples LEFT OUTER JOIN stockmaster
                        ON stockmaster.stockid=qasamples.prodspeckey
                        WHERE cert='1'
                        ORDER BY lotkey";

    $ResultSelection=DB_query($SQLSpecSelect);
    
    echo '<select name="QASampleID" class="db-select" style="font-family: \'JetBrains Mono\', monospace;">';
    echo '<option value="">' . str_pad(__('Lot/Serial'), 15, ' ') . ' | ' . str_pad(__('Item'), 15, ' ') . ' | ' . __('Description') . '</option>';
    while ($MyRowSelection=DB_fetch_array($ResultSelection)){
        $displayText = str_pad($MyRowSelection['lotkey'], 15, ' ') . ' | ' . 
                       str_pad($MyRowSelection['prodspeckey'], 15, ' ') . ' | ' . 
                       $MyRowSelection['description'];
        echo '<option value="' . $MyRowSelection['sampleid'] . '">' . htmlspecialchars($displayText) . '</option>';
    }
    echo '</select>
                            </div>

                            <button type="submit" name="PickSpec" class="db-btn db-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                ' . __('Generate COA PDF') . '
                            </button>
                        </div>
                    </form>

                    <div class="db-divider">' . __('Or') . '</div>

                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <div class="db-field-group">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
                                <div class="db-field">
                                    <label class="db-label">' . __('Enter Item Code') . '</label>
                                    <input type="text" name="ProdSpec" class="db-input" placeholder="e.g. SKU123" maxlength="25" />
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('Enter Lot Number') . '</label>
                                    <input type="text" name="LotKey" class="db-input" placeholder="e.g. LOT456" maxlength="25" />
                                </div>
                            </div>
                            <button type="submit" name="PickSpec" class="db-btn db-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                ' . __('Identify and Generate COA') . '
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';

	include(__DIR__ . '/includes/footer.php');
	exit();
}

$ErrMsg = __('There was a problem retrieving the Lot Information') . ' ' .$SelectedCOA . ' ' . __('from the database');
if (isset($SelectedCOA)) {
	$SQL = "SELECT lotkey,
					description,
					name,
					method,
					qatests.units,
					type,
					testvalue,
					sampledate,
					prodspeckey,
					groupby
				FROM qasamples INNER JOIN sampleresults
				ON sampleresults.sampleid=qasamples.sampleid
				INNER JOIN qatests
				ON qatests.testid=sampleresults.testid
				LEFT OUTER JOIN stockmaster on stockmaster.stockid=qasamples.prodspeckey
				WHERE qasamples.lotkey='" .$SelectedCOA."'
				AND qasamples.prodspeckey='" .$SelectedSpec."'
				AND qasamples.cert='1'
				AND sampleresults.showoncert='1'
				ORDER by groupby, sampleresults.testid";
} else {
	$SQL = "SELECT lotkey,
					description,
					name,
					method,
					qatests.units,
					type,
					testvalue,
					sampledate,
					prodspeckey,
					groupby
				FROM qasamples INNER JOIN sampleresults
				ON sampleresults.sampleid=qasamples.sampleid
				INNER JOIN qatests
				ON qatests.testid=sampleresults.testid
				LEFT OUTER JOIN stockmaster on stockmaster.stockid=qasamples.prodspeckey
				LEFT OUTER JOIN prodspecgroups on prodspecgroups.groupname=qatests.groupby
				WHERE qasamples.sampleid='" .$QASampleID."'
				AND qasamples.cert='1'
				AND sampleresults.showoncert='1'
				ORDER by groupbyNo, sampleresults.testid";
}
$Result = DB_query($SQL, $ErrMsg);

if (DB_num_rows($Result)==0){
	$Title = __('Print Certificate of Analysis Error');
	include(__DIR__ . '/includes/header.php');
	echo '<div class="centre">
			<br />
			<br />
			<br />';
	prnMsg( __('Unable to Locate Lot') . ' : ' . $SelectedCOA . ' ', 'error');
	echo '<br />
			<br />
			<br />
			<table class="table_index">
			<tr>
				<td class="menu_group_item">
					<ul><li><a href="'. $RootPath . '/PDFCOA.php">' . __('Certificate of Analysis') . '</a></li></ul>
				</td>
			</tr>
			</table>
			</div>
			<br />
			<br />
			<br />';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if ($QASampleID>'') {
	$MyRow=DB_fetch_array($Result);
	$SelectedCOA=$MyRow['lotkey'];
	DB_data_seek($Result,0);
}

// Prepare HTML for PDF
$HTML = '
<html>
<head>
<style>
	body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; }
	h2.title { text-align: center; background: #f0f0f0; padding: 8px; }
	table.certificate { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
	table.certificate th, table.certificate td { border: 1px solid #ccc; padding: 4px 8px; font-size: 12px; }
	table.certificate th { background: #e0e0e0; }
	.section-title { font-weight: bold; font-size: 13px; padding-bottom: 4px; }
	.section-trailer { font-size: 10px; margin-bottom: 10px; }
	.disclaimer { font-size: 10px; margin-top: 16px; }
</style>
</head>
<body>
';

$HTML .= '<h2 class="title">' . __('Certificate of Analysis') . ' ' . htmlspecialchars($SelectedCOA) . '</h2>';

// Optionally include a header file (logic from includes/PDFCOAHeader.php if needed as HTML)
// For now, add a basic header:
$HTML .= '<table style="width:100%;margin-bottom:10px;"><tr>
<td style="width:33%;">' . __('Lot:') . ' ' . htmlspecialchars($SelectedCOA) . '</td>
<td style="width:33%;text-align:center;">' . __('Date:') . ' ' . date('Y-m-d') . '</td>
<td style="width:33%;text-align:right;">' . __('Item:') . ' ' . (isset($SelectedSpec)?htmlspecialchars($SelectedSpec):'') . '</td>
</tr></table>';

$SectionsArray=[];
$result2 = DB_query("SELECT groupname, headertitle, trailertext, labels, numcols FROM prodspecgroups", $db);
while ($MyGroupRow = DB_fetch_array($result2)) {
	if ($MyGroupRow['numcols']==2) {
		$cols=array(240,265);
	} else {
		$cols=array(260,110,135);
	}
	$SectionsArray[] = array(
		$MyGroupRow['groupname'],
		$MyGroupRow['numcols'],
		$MyGroupRow['headertitle'],
		$MyGroupRow['trailertext'],
		$cols,
		explode(",",$MyGroupRow['labels'])
	);
}

$CurSection = 'NULL';
$SectionTitle = '';
$SectionTrailer = '';
$PrevTrailer = '';
$PrintTrailer = 1;
$tableOpen = false;

while ($MyRow=DB_fetch_array($Result)){
	if ($MyRow['description']=='') {
		$MyRow['description']=$MyRow['prodspeckey'];
	}
	$Spec = htmlspecialchars($MyRow['description']);
	$SampleDate = ConvertSQLDate($MyRow['sampledate']);

	foreach($SectionsArray as $Row) {
		if ($MyRow['groupby']==$Row[0]) {
			$SectionColSizes = $Row[4];
			$SectionColLabs = $Row[5];
			$SectionTitle = $Row[2];
			$SectionTrailer = $Row[3];
		}
	}

	if ($CurSection != $MyRow['groupby']) {
		if ($CurSection != 'NULL' && $PrintTrailer==1 && $PrevTrailer != '') {
			if ($tableOpen) {
				$HTML .= '</table>';
				$tableOpen = false;
			}
			$HTML .= '<div class="section-trailer">'.htmlspecialchars($PrevTrailer).'</div>';
		}
		$CurSection = $MyRow['groupby'];
		$HTML .= '<div class="section-title">'.htmlspecialchars($SectionTitle).'</div>';
		$HTML .= '<table class="certificate"><tr>';
		foreach ($SectionColLabs as $ColLabel) {
			$HTML .= '<th>'.htmlspecialchars($ColLabel).'</th>';
		}
		$HTML .= '</tr>';
		$tableOpen = true;
		$SectionHeading = 1;
		$PrevTrailer = $SectionTrailer;
	}

	$Value = '';
	if ($MyRow['testvalue'] > '') {
		$Value = $MyRow['testvalue'];
	}
	if (strtoupper($Value) !== 'NB' && strtoupper($Value) !== 'NO BREAK') {
		$Value .= ' ' . $MyRow['units'];
	}
	$rowHtml = '<tr>';
	for ($x = 0; $x < count($SectionColLabs); $x++) {
		$DispValue = match ($x) {
			0       => htmlspecialchars($MyRow['name']),
			1       => htmlspecialchars($Value),
			2       => htmlspecialchars($MyRow['method']),
			default => '',
		};
		$rowHtml .= '<td>'.$DispValue.'</td>';
	}
	$rowHtml .= '</tr>';
	$HTML .= $rowHtml;
}
if ($tableOpen) {
	$HTML .= '</table>';
}

if ($SectionTrailer>'') {
	$HTML .= '<div class="section-trailer">'.htmlspecialchars($SectionTrailer).'</div>';
}

// Disclaimer
$SQL = "SELECT confvalue FROM config WHERE confname='QualityCOAText'";
$Result = DB_query($SQL, $ErrMsg);
$MyRow = DB_fetch_array($Result);
$Disclaimer = $MyRow[0];
if ($Disclaimer > '') {
	$HTML .= '<div class="disclaimer">'.htmlspecialchars($Disclaimer).'</div>';
}

$HTML .= '</body></html>';

$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
$DomPDF->loadHtml($HTML);
$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
$DomPDF->render();

// Output to browser
$filename = $_SESSION['DatabaseName'] . 'COA' . date('Y-m-d') . '.pdf';
$DomPDF->stream($filename, array("Attachment" => false));

exit;
