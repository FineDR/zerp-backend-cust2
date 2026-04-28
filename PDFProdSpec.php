<?php
require (__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include (__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['KeyValue'])) {
	$SelectedProdSpec = $_GET['KeyValue'];
} elseif (isset($_POST['KeyValue'])) {
	$SelectedProdSpec = $_POST['KeyValue'];
} else {
	$SelectedProdSpec = '';
}

//Get Out if we have no product specification
# if (isset($SelectedProdSpec) and $SelectedProdSpec != '') {
if ($SelectedProdSpec != '') {

	/*retrieve the order details from the database to print */
	$ErrMsg = __('There was a problem retrieving the Product Specification') . ' ' . $SelectedProdSpec . ' ' . __('from the database');

	$SQL = "SELECT keyval,
				description,
				longdescription,
				prodspecs.testid,
				name,
				method,
				qatests.units,
				type,
				numericvalue,
				prodspecs.targetvalue,
				prodspecs.rangemin,
				prodspecs.rangemax,
				groupby
			FROM prodspecs INNER JOIN qatests
			ON qatests.testid=prodspecs.testid
			LEFT OUTER JOIN stockmaster on stockmaster.stockid=prodspecs.keyval
			LEFT OUTER JOIN prodspecgroups on prodspecgroups.groupname=qatests.groupby
			WHERE prodspecs.keyval='" . $SelectedProdSpec . "'
			AND prodspecs.showonspec='1'
			ORDER by groupbyNo, prodspecs.testid";

	$Result = DB_query($SQL, $ErrMsg);

	//If there are no rows, there's a problem.
	if (DB_num_rows($Result) == 0) {
		$Title = __('Print Product Specification Error');
		include ('includes/header.php');
		echo '<div class="centre">';
		prnMsg(__('Unable to Locate Specification') . ' : ' . $_SelectedProdSpec . ' ', 'error');
		echo '<table class="table_index">
			<tr>
				<td class="menu_group_item">
					<ul><li><a href="' . $RootPath . '/PDFProdSpec.php">' . __('Product Specifications') . '</a></li></ul>
				</td>
			</tr>
			</table>
			</div>';
		include ('includes/footer.php');
		exit();
	}

	// Prepare product spec data
	$SectionsArray = [];
	$Result2 = DB_query("SELECT groupname, headertitle, trailertext, labels, numcols FROM prodspecgroups", $db);
	while ($MyGroupRow = DB_fetch_array($Result2)) {
		if ($MyGroupRow['numcols'] == 2) {
			$Align = array('left', 'center');
			$Cols = array(240, 265);
		}
		else {
			$Align = array('left', 'center', 'center');
			$Cols = array(260, 110, 135);
		}
		$SectionsArray[] = array($MyGroupRow['groupname'], $MyGroupRow['numcols'], $MyGroupRow['headertitle'], $MyGroupRow['trailertext'], $Cols, explode(",", $MyGroupRow['labels']), $Align);
	}
	DB_data_seek($Result2, 0);

	// Build HTML for DomPDF
	$HTML = '';
	$HTML .= '<html>';
	$HTML .= '<head>';
	$HTML .= '<style>
		body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; }
		.title { font-size: 22px; font-weight: bold; text-align: center; margin-bottom: 10px; }
		.subtitle { font-size: 15px; font-weight: bold; text-align: center; margin-bottom: 10px; }
		.sectiontitle { background: #ccc; font-weight: bold; text-align: center; }
		.table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
		.table th, .table td { border: 1px solid #999; padding: 4px; }
		.trailer { font-size: 10px; margin-top: 4px; margin-bottom: 10px; color: #333; }
		.disclaimer, .footertext { font-size: 9px; color: #333; margin-top: 12px; }
	</style>';
	$HTML .= '</head>';
	$HTML .= '<body>';

	$Spec = '';
	$SpecDesc = '';
	$CurrentSection = '';
	$SectionTrailer = '';
	$SectionTitle = '';
	$SectionColLabs = [];
	$SectionColSizes = [];
	$SectionAlign = [];
	$First = true;
	$RowsBySection = [];

	// Organize rows by section/group
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['description'] == '') {
			$MyRow['description'] = $MyRow['keyval'];
		}
		$Spec = $MyRow['description'];
		$SpecDesc = $MyRow['longdescription'];
		$Group = $MyRow['groupby'];
		if (!isset($RowsBySection[$Group])) {
			$RowsBySection[$Group] = [];
		}
		$RowsBySection[$Group][] = $MyRow;
	}

	$HTML .= '<div class="title">' . __('Product Specification') . '</div>';
	$HTML .= '<div class="subtitle">' . htmlspecialchars($Spec, ENT_QUOTES, 'UTF-8') . '</div>';
	if ($SpecDesc) {
		$HTML .= '<div style="text-align:center; margin-bottom: 14px;">' . htmlspecialchars($SpecDesc, ENT_QUOTES, 'UTF-8') . '</div>';
	}

	// Loop through sections/groups
	foreach ($SectionsArray as $Section) {
		list($Groupname, $numcols, $headertitle, $trailertext, $Cols, $labels, $Align) = $Section;
		if (empty($RowsBySection[$Groupname])) continue;

		$HTML .= '<table class="table">';
		$HTML .= '<tr><td colspan="' . count($labels) . '" class="sectiontitle">' . htmlspecialchars($headertitle, ENT_QUOTES, 'UTF-8') . '</td></tr>';
		$HTML .= '<tr>';
		foreach ($labels as $colLabel) {
			$HTML .= '<th>' . htmlspecialchars($colLabel, ENT_QUOTES, 'UTF-8') . '</th>';
		}
		$HTML .= '</tr>';
		foreach ($RowsBySection[$Groupname] as $MyRow) {
			// Calculate Value
			$Value = '';
			if ($MyRow['targetvalue'] > '') {
				$Value = $MyRow['targetvalue'];
			}
			elseif ($MyRow['rangemin'] > '' or $MyRow['rangemax'] > '') {
				if ($MyRow['rangemin'] > '' and $MyRow['rangemax'] == '') {
					$Value = '> ' . $MyRow['rangemin'];
				}
				elseif ($MyRow['rangemin'] == '' and $MyRow['rangemax'] > '') {
					$Value = '< ' . $MyRow['rangemax'];
				}
				else {
					$Value = $MyRow['rangemin'] . ' - ' . $MyRow['rangemax'];
				}
			}
			if (strtoupper($Value) != 'NB' && strtoupper($Value) != 'NO BREAK') {
				$Value .= ' ' . $MyRow['units'];
			}
			$HTML .= '<tr>';
			for ($x = 0;$x < count($labels);$x++) {
				$DispValue = match ($x) {
					0       => $MyRow['name'],
					1       => $Value,
					2       => $MyRow['method'],
					default => '',
				};
				$HTML .= '<td style="text-align: ' . $Align[$x] . ';">' . htmlspecialchars($DispValue, ENT_QUOTES, 'UTF-8') . '</td>';
			}
			$HTML .= '</tr>';
		}
		if ($trailertext) {
			$HTML .= '<tr><td colspan="' . count($labels) . '" class="trailer">' . htmlspecialchars($trailertext, ENT_QUOTES, 'UTF-8') . '</td></tr>';
		}
		$HTML .= '</table>';
	}

	// Disclaimer from config
	$Disclaimer = __('The information provided on this datasheet should only be used as a guideline. Actual lot to lot values will vary.');
	$SQL = "SELECT confvalue FROM config WHERE confname='QualityProdSpecText'";
	$Result = DB_query($SQL, $ErrMsg);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow && isset($MyRow[0]) && $MyRow[0]) {
		$Disclaimer = $MyRow[0];
	}
	$HTML .= '<div class="disclaimer">' . htmlspecialchars($Disclaimer, ENT_QUOTES, 'UTF-8') . '</div>';

	$HTML .= '</body>';
	$HTML .= '</html>';

	// Output PDF using DomPDF
	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options

	$DomPDF->loadHtml($HTML, 'UTF-8');
	$DomPDF->setPaper('letter');
	$DomPDF->render();

	$FileName = $_SESSION['DatabaseName'] . '_ProductSpecification_' . date('Y-m-d') . '.pdf';

	$DomPDF->stream($FileName, array("Attachment" => false));
} else {

	$Title = __('Select Product Specification To Print');
	$ViewTopic = 'QualityAssurance';
	$BookMark = '';
	include ('includes/header.php');

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
                    ' . __('Quality Assurance / Compliance') . '
                </div>
                <h1 class="db-page-title">' . __('Product Specifications') . '</h1>
            </div>

            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        ' . __('Select Specification for PDF Generation') . '
                    </h3>
                </div>
                <div class="db-card-body">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        
                        <div class="db-field-group">
                            <div class="db-field">
                                <label class="db-label">' . __('Select Existing Specification') . '</label>';
                                
    $SQLSpecSelect = "SELECT DISTINCT(keyval),
                            description
                        FROM prodspecs LEFT OUTER JOIN stockmaster
                        ON stockmaster.stockid=prodspecs.keyval";
    $ResultSelection = DB_query($SQLSpecSelect);
    
    echo '<select name="KeyValue" class="db-select">';
    while ($MyRowSelection = DB_fetch_array($ResultSelection)) {
        echo '<option value="' . $MyRowSelection['keyval'] . '">' . $MyRowSelection['keyval'] . ' - ' . htmlspecialchars($MyRowSelection['description'], ENT_QUOTES, 'UTF-8', false) . '</option>';
    }
    echo '</select>
                            </div>

                            <button type="submit" name="PickSpec" class="db-btn db-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                ' . __('Generate Specification PDF') . '
                            </button>
                        </div>
                    </form>

                    <div class="db-divider">' . __('Or') . '</div>

                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <div class="db-field-group">
                            <div class="db-field">
                                <label class="db-label">' . __('Search by Specification Name / SKU') . '</label>
                                <input type="text" name="KeyValue" class="db-input" placeholder="' . __('Enter SKU or Spec Name...') . '" maxlength="25" />
                            </div>
                            <button type="submit" name="PickSpec" class="db-btn db-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                ' . __('Find and Generate PDF') . '
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';

	include ('includes/footer.php');
}

