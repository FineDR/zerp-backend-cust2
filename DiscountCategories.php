<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Discount Categories Maintenance');
$ViewTopic = "SalesOrders";
$BookMark = "DiscountMatrix";

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
        max-width: 1400px;
        margin: 0 auto;
        gap: 20px;
    }
	
    .breadcrumb-wrap { 
        font-size: 0.6rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; 
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
        flex-wrap: wrap;
        gap: 10px;
	}
	.db-card-title {
		font-size: 0.82rem;
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
        margin-bottom: 20px;
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
        width: 100%; border-radius: 10px; height: 46px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }

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
	.architect-btn-secondary { background: #f3f4f6; color: #4b5563; box-shadow: none; }

    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 380px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
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

    .search-results-btn {
        display: block; width: 100%; text-align: left; padding: 10px 15px;
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;
        margin-bottom: 5px; cursor: pointer; transition: all 0.2s;
        font-weight: 600; color: #334155; font-size: 0.85rem;
    }
    .search-results-btn:hover { background: #f0fdf4; border-color: #059669; color: #065f46; }

    @media (max-width: 1024px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['stockID'])) {
	$_POST['StockID']=$_POST['stockID'];
} elseif (isset($_GET['StockID'])) {
	$_POST['StockID']=$_GET['StockID'];
	$_POST['ChooseOption']=1;
	$_POST['SelectChoice']=1;
}

if (isset($_POST['submit']) and !isset($_POST['SubmitCategory'])) {
	$InputError = 0;
	$Result = DB_query("SELECT stockid FROM stockmaster WHERE mbflag <>'K' AND mbflag<>'D' AND stockid='" . mb_strtoupper($_POST['StockID']) . "'");
	if (DB_num_rows($Result)==0){
		$InputError = 1;
		prnMsg(__('The stock item entered must be set up as either a manufactured or purchased or assembly item'),'warn');
	}
	if ($InputError !=1) {
		$SQL = "UPDATE stockmaster SET discountcategory='" . $_POST['DiscountCategory'] . "'
				WHERE stockid='" . mb_strtoupper($_POST['StockID']) . "'";
		$Result = DB_query($SQL);
		prnMsg(__('The stock master has been updated with this discount category'),'success');
		unset($_POST['DiscountCategory']); unset($_POST['StockID']);
	}
} elseif (isset($_GET['Delete']) and $_GET['Delete']=='yes') {
	$SQL="UPDATE stockmaster SET discountcategory='' WHERE stockid='" . trim(mb_strtoupper($_GET['StockID'])) ."'";
	$Result = DB_query($SQL);
	prnMsg( __('The stock master record has been updated to no discount category'),'success');
} elseif (isset($_POST['SubmitCategory'])) {
	$SQL = "SELECT stockid FROM stockmaster WHERE categoryid='".$_POST['stockcategory']."'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result)>0){
		$SQL="UPDATE stockmaster SET discountcategory='".$_POST['DiscountCategory']."' WHERE categoryid='".$_POST['stockcategory']."'";
		$Result = DB_query($SQL);
	} else {
		prnMsg(__('There are no stock defined for this stock category'),'error');
	}
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
                        ' . __('Discounts') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="main-form" name="' . (isset($_POST['SubmitCategory']) ? 'SubmitCategory' : 'submit') . '" class="architect-btn">
                        <i class="fas fa-save"></i> ' . __('Apply Changes') . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">';

        if (!isset($_POST['SelectChoice'])) {
            // Initial Selection Step
            echo '<main class="db-main" style="grid-column: 1 / -1;">
                    <div class="db-card" style="max-width: 600px; margin: 0 auto;">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-filter"></i> ' . __('Scope Selection') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <form method="post" id="choose" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '">
                                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                                <field>
                                    <label>' . __('Assign categories for...') . '</label>
                                    <select name="ChooseOption">
                                        <option value="1">' . __('A single stock item') . '</option>
                                        <option value="2">' . __('A complete stock category') . '</option>
                                    </select>
                                </field>
                                <button type="submit" name="SelectChoice" class="architect-btn" style="width: 100%;">
                                    ' . __('Next Step') . ' <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                  </main>';
        } else {
            // Main Dashboard Step
            echo '<main class="db-main" style="min-width: 0;">';
            
            // Results Table
            if (!isset($_POST['DiscCat'])){
                $SQL = "SELECT DISTINCT discountcategory FROM stockmaster WHERE discountcategory <>''";
                $Result = DB_query($SQL);
                if (DB_num_rows($Result)>0){
                    $MyRow = DB_fetch_array($Result);
                    $_POST['DiscCat'] = $MyRow['discountcategory'];
                } else { $_POST['DiscCat']='0'; }
            }

            if ($_POST['DiscCat']!='0'){
                $SQL = "SELECT stockmaster.stockid, stockmaster.description, discountcategory
                        FROM stockmaster WHERE discountcategory='" . $_POST['DiscCat'] . "' ORDER BY stockmaster.stockid";
                $Result = DB_query($SQL);
                
                echo '<div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-tags"></i> ' . __('Members of Category') . ': ' . $_POST['DiscCat'] . '</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>' . __('Item Code') . '</th>
                                        <th>' . __('Description') . '</th>
                                        <th style="width: 80px;"></th>
                                    </tr>
                                </thead>
                                <tbody>';
                while ($MyRow = DB_fetch_array($Result)) {
                    $DeleteURL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?Delete=yes&amp;StockID=' . urlencode($MyRow['stockid']) . '&amp;DiscountCategory=' . urlencode($MyRow['discountcategory']);
                    echo '<tr>
                            <td style="font-weight: 700;">', $MyRow['stockid'], '</td>
                            <td style="font-size: 0.85rem; color: #64748b;">', $MyRow['description'], '</td>
                            <td style="text-align: right;">
                                <a href="', $DeleteURL, '" style="color: #dc2626;" onclick="return confirm(\'' . __('Remove item from this category?') . '\');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>';
                }
                echo '      </tbody>
                            </table>
                        </div>
                    </div>';
            } else {
                echo '<div class="db-card">
                        <div class="db-card-body" style="text-align: center; padding: 40px; color: #64748b;">' . __('No categories defined yet.') . '</div>
                      </div>';
            }
            
            // Search Results (if single item mode)
            if (isset($_POST['search'])) {
                $SQL = "SELECT stockid, description FROM stockmaster WHERE 1=1";
                if ($_POST['PartID']!='') $SQL .= " AND stockid " . LIKE . " '%".$_POST['PartID']."%'";
                if ($_POST['PartDesc']!='') $SQL .= " AND description " . LIKE . " '%".$_POST['PartDesc']."%'";
                $Result = DB_query($SQL);
                
                echo '<div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Select Item') . '</h3></div>
                        <div class="db-card-body">';
                while ($MyRow=DB_fetch_array($Result)) {
                    echo '<form method="post" style="display:inline;">
                            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                            <input type="hidden" name="ChooseOption" value="'.$_POST['ChooseOption'].'" />
                            <input type="hidden" name="SelectChoice" value="'.$_POST['SelectChoice'].'" />
                            <button type="submit" name="stockID" value="'.$MyRow['stockid'].'" class="search-results-btn">
                                '.$MyRow['stockid'].' - '.$MyRow['description'].'
                            </button>
                        </form>';
                }
                echo '  </div>
                    </div>';
            }

            echo '  <div style="margin-top: 10px;">
                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" class="architect-btn architect-btn-secondary">
                           <i class="fas fa-undo"></i> ' . __('Back to Selection') . '
                        </a>
                    </div>
                  </main>

                  <aside class="db-sidebar" style="min-width: 0;">
                    <form id="main-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <input type="hidden" name="ChooseOption" value="'.$_POST['ChooseOption'].'" />
                        <input type="hidden" name="SelectChoice" value="'.$_POST['SelectChoice'].'" />

                        <div class="db-card">
                            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-cog"></i> ' . __('Category Assignment') . '</h3></div>
                            <div class="db-card-body">
                                <field>
                                    <label>' . __('Target Category') . '</label>
                                    <input type="text" name="DiscountCategory" maxlength="2" placeholder="e.g. A1" value="' . ($_POST['DiscCat'] != '0' ? $_POST['DiscCat'] : '') . '" />
                                </field>';

                                if ($_POST['ChooseOption'] == 1) {
                                    echo '<field>
                                            <label>' . __('Stock Code') . '</label>
                                            <input type="text" name="StockID" placeholder="' . __('Search or enter ID') . '" value="' . ($_POST['StockID'] ?? '') . '" />
                                          </field>
                                          <div style="background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                                            <h4 style="font-size: 0.65rem; color: #64748b; text-transform: uppercase; margin: 0 0 10px 0;">' . __('Item Lookup') . '</h4>
                                            <field><input type="text" name="PartID" placeholder="' . __('Partial ID...') . '" /></field>
                                            <field><input type="text" name="PartDesc" placeholder="' . __('Partial Desc...') . '" /></field>
                                            <button type="submit" name="search" class="architect-btn" style="width: 100%; background: #6b7280;">' . __('Search Item') . '</button>
                                          </div>';
                                } else {
                                    echo '<field>
                                            <label>' . __('Apply to Category') . '</label>';
                                    $Result = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
                                    echo '<select name="stockcategory">';
                                    while ($MyRow=DB_fetch_array($Result)) {
                                        echo '<option value="'.$MyRow['categoryid'].'">' . $MyRow['categorydescription'] . '</option>';
                                    }
                                    echo '</select></field>';
                                }

                                echo '<button type="submit" name="' . ($_POST['ChooseOption'] == 1 ? 'submit' : 'SubmitCategory') . '" class="architect-btn" style="width: 100%;">
                                        <i class="fas fa-check-circle"></i> ' . __('Update Assignments') . '
                                      </button>
                            </div>
                        </div>';

                        $Result = DB_query("SELECT DISTINCT discountcategory FROM stockmaster WHERE discountcategory <>''");
                        if (DB_num_rows($Result) > 0) {
                            echo '<div class="db-card" style="background: #f8fafc; border-style: dashed;">
                                    <div class="db-card-body" style="padding: 15px;">
                                        <label style="font-size: 0.65rem; font-weight: 900; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">' . __('View Existing Category') . '</label>
                                        <select name="DiscCat" onchange="this.form.submit()">';
                                        while ($MyRow = DB_fetch_array($Result)){
                                            echo '<option ' . ($MyRow['discountcategory']==$_POST['DiscCat'] ? 'selected' : '') . ' value="' . $MyRow['discountcategory'] . '">' . $MyRow['discountcategory']  . '</option>';
                                        }
                            echo '      </select>
                                    </div>
                                  </div>';
                        }
            echo '  </form>
                  </aside>';
        }

echo '  </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
