<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Discount Matrix Maintenance');
$ViewTopic = 'SalesOrders';
$BookMark = 'DiscountMatrix';

// Inject premium Architect Workspace styles
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: 20px 15px; background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; box-sizing: border-box; }
	
	.premium-header { 
        margin: -20px -15px 30px -15px;
        padding: 20px; 
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        max-width: 1600px;
        margin: 0 auto;
        gap: 20px;
    }
	
    .breadcrumb-wrap { 
        font-size: 0.65rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; 
        display: flex; align-items: center; gap: 8px; text-transform: uppercase; 
        letter-spacing: 1px; opacity: 0.6;
    }
    .breadcrumb-wrap a { color: inherit; text-decoration: none; }
    .breadcrumb-wrap a:hover { text-decoration: underline; opacity: 1; }

	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-md);
		overflow: hidden;
        margin-bottom: 30px;
        width: 100%;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
	}
	.db-card-title {
		font-size: 0.8rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 25px; }
	
    field {
        display: block;
        margin-bottom: 18px;
    }
    field label {
        font-size: 0.65rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 6px;
        opacity: 0.7;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    .fieldhelp { font-size: 0.75rem; color: #64748b; margin-top: 6px; display: block; font-weight: 500; }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 320px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1600px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }

    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-emerald { background: #d1fae5; color: #065f46; }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 1; }
        .db-bottom-layout main { order: 2; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

$Errors = array();
$i = 1;

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (!is_numeric(filter_number_format($_POST['QuantityBreak']))){
		prnMsg( __('The quantity break must be entered as a positive number'),'error');
		$InputError =1;
		$Errors[$i] = 'QuantityBreak';
		$i++;
	}
	if (filter_number_format($_POST['QuantityBreak'])<=0){
		prnMsg( __('The quantity of all items on an order in the discount category') . ' ' . $_POST['DiscountCategory'] . ' ' . __('at which the discount will apply is 0 or less than 0') . '. ' . __('Positive numbers are expected for this entry'),'warn');
		$InputError =1;
		$Errors[$i] = 'QuantityBreak';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['DiscountRate']))){
		prnMsg( __('The discount rate must be entered as a positive number'),'warn');
		$InputError =1;
		$Errors[$i] = 'DiscountRate';
		$i++;
	}
	if (filter_number_format($_POST['DiscountRate'])<=0 OR filter_number_format($_POST['DiscountRate'])>100){
		prnMsg( __('The discount rate applicable for this record is either less than 0% or greater than 100%') . '. ' . __('Numbers between 1 and 100 are expected'),'warn');
		$InputError =1;
		$Errors[$i] = 'DiscountRate';
		$i++;
	}

	if ($InputError !=1) {
		$SQL = "INSERT INTO discountmatrix (salestype, discountcategory, quantitybreak, discountrate)
					VALUES('" . $_POST['SalesType'] . "', '" . $_POST['DiscountCategory'] . "', '" . filter_number_format($_POST['QuantityBreak']) . "', '" . (filter_number_format($_POST['DiscountRate'])/100) . "')";
		DB_query($SQL);
		prnMsg( __('The discount matrix record has been added'),'success');
		unset($_POST['DiscountCategory']); unset($_POST['SalesType']); unset($_POST['QuantityBreak']); unset($_POST['DiscountRate']);
	}
} elseif (isset($_GET['Delete']) and $_GET['Delete']=='yes') {
	$SQL="DELETE FROM discountmatrix WHERE discountcategory='" .$_GET['DiscountCategory'] . "' AND salestype='" . $_GET['SalesType'] . "' AND quantitybreak='" . $_GET['QuantityBreak']."'";
	DB_query($SQL);
	prnMsg( __('The discount matrix record has been deleted'),'success');
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=Sales">' . __('Sales') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Discount Matrix') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="matrix-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . __('Create Matrix Entry') . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <aside class="db-sidebar" style="min-width: 0;">
                <form id="matrix-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('New Matrix Rule') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Sales Type / Price List') . '</label>
                                <select name="SalesType">';
                                $SQL_types = "SELECT typeabbrev, sales_type FROM salestypes";
                                $Result_types = DB_query($SQL_types);
                                while ($MyRow = DB_fetch_array($Result_types)){
                                    echo '<option ' . ((isset($_POST['SalesType']) && $MyRow['typeabbrev']==$_POST['SalesType']) ? 'selected' : '') . ' value="' . $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';
                                }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Discount Category') . '</label>';
                                $SQL_cats = "SELECT DISTINCT discountcategory FROM stockmaster WHERE discountcategory <>''";
                                $Result_cats = DB_query($SQL_cats);
                                if (DB_num_rows($Result_cats) > 0) {
                                    echo '<select name="DiscountCategory">';
                                    while ($MyRow = DB_fetch_array($Result_cats)){
                                        echo '<option ' . ((isset($_POST['DiscountCategory']) && $MyRow['discountcategory']==$_POST['DiscountCategory']) ? 'selected' : '') . ' value="' . $MyRow['discountcategory'] . '">' . $MyRow['discountcategory'] . '</option>';
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<input type="text" value="' . __('No categories defined') . '" disabled />
                                          <input type="hidden" name="DiscountCategory" value="" />';
                                }
echo '                      </field>
                            <field>
                                <label>' . __('Quantity Break') . '</label>
                                <input type="number" name="QuantityBreak" required value="' . ($_POST['QuantityBreak'] ?? '') . '" placeholder="e.g. 100" />
                                <span class="fieldhelp">' . __('Minimum quantity to trigger discount') . '</span>
                            </field>
                            <field>
                                <label>' . __('Discount Rate') . ' (%)</label>
                                <input type="text" name="DiscountRate" required value="' . ($_POST['DiscountRate'] ?? '') . '" placeholder="e.g. 5.5" />
                                <span class="fieldhelp">' . __('Percentage discount to apply (1-100)') . '</span>
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . __('Add Rule to Matrix') . '
                            </button>
                        </div>
                    </div>
                </form>
            </aside>

            <main class="db-main" style="min-width: 0;">';

            $SQL_matrix = "SELECT sales_type, salestype, discountcategory, quantitybreak, discountrate
                           FROM discountmatrix INNER JOIN salestypes ON discountmatrix.salestype=salestypes.typeabbrev
                           ORDER BY salestype, discountcategory, quantitybreak";
            $Result_matrix = DB_query($SQL_matrix);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-th-list"></i> ' . __('Active Discount Matrix') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Price List') . '</th>
                                    <th>' . __('Category') . '</th>
                                    <th style="text-align: right;">' . __('Qty Break') . '</th>
                                    <th style="text-align: right;">' . __('Discount') . '</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result_matrix)) {
                                $DelURL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?Delete=yes&amp;SalesType=' . $MyRow['salestype'] . '&amp;DiscountCategory=' . $MyRow['discountcategory'] . '&amp;QuantityBreak=' . $MyRow['quantitybreak'];
                                echo '<tr>
                                        <td style="font-weight: 700;">', $MyRow['sales_type'], '</td>
                                        <td><span class="badge badge-emerald">', $MyRow['discountcategory'], '</span></td>
                                        <td style="text-align: right; font-weight: 600;">', number_format($MyRow['quantitybreak']), '</td>
                                        <td style="text-align: right; font-weight: 900; color: #059669;">', $MyRow['discountrate']*100, '%</td>
                                        <td style="text-align: right;">
                                            <a href="', $DelURL, '" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
