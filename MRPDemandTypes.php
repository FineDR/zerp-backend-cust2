<?php

require(__DIR__ . '/includes/session.php');

$Title = __('MRP Demand Types');
$ViewTopic = 'MRP';
$BookMark = '';

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
        flex-wrap: wrap;
        gap: 10px;
	}
	.db-card-title {
		font-size: 0.85rem;
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
        font-size: 0.7rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 1px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 8px;
        opacity: 0.7;
    }
    field input[type="text"] {
        width: 100%; border-radius: 10px; height: 50px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 15px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    field input:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    
    .fieldtext {
        font-weight: 700; color: #1f2937; padding: 12px 0; border-bottom: 1px dashed #e5e7eb; margin-bottom: 15px; font-size: 1rem;
    }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 14px 28px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.9rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 350px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 500px; }
    table.modern-table th { 
        text-align: left; padding: 15px 20px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; color: #334155; }

    .action-link { 
        color: #059669; font-weight: 700; text-decoration: none; 
        display: inline-flex; align-items: center; gap: 5px; font-size: 0.85rem; 
    }
    .action-link:hover { color: #065f46; }
    .action-link.delete { color: #dc2626; }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
    @media (max-width: 600px) {
        .premium-header { padding: 15px; margin-bottom: 20px; }
        .db-card-body { padding: 15px; }
        h1 { font-size: 1.4rem !important; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

//SelectedDT is the Selected MRPDemandType
if (isset($_POST['SelectedDT'])){
	$SelectedDT = trim(mb_strtoupper($_POST['SelectedDT']));
} elseif (isset($_GET['SelectedDT'])){
	$SelectedDT = trim(mb_strtoupper($_GET['SelectedDT']));
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	if (trim(mb_strtoupper($_POST['MRPDemandType']) == 'WO') or
	   trim(mb_strtoupper($_POST['MRPDemandType']) == 'SO')) {
		$InputError = 1;
		prnMsg(__('The Demand Type is reserved for the system'),'error');
	}

	if (mb_strlen($_POST['MRPDemandType']) < 1) {
		$InputError = 1;
		prnMsg(__('The Demand Type code must be at least 1 character long'),'error');
	}
	if (mb_strlen($_POST['Description'])<3) {
		$InputError = 1;
		prnMsg(__('The Demand Type description must be at least 3 characters long'),'error');
	}

	if (isset($SelectedDT) AND $InputError !=1) {
		$SQL = "UPDATE mrpdemandtypes SET description = '" . $_POST['Description'] . "'
				WHERE mrpdemandtype = '" . $SelectedDT . "'";
		$Msg = __('The demand type record has been updated');
	} elseif ($InputError !=1) {
		$SQL = "INSERT INTO mrpdemandtypes (mrpdemandtype,
						description)
					VALUES ('" . trim(mb_strtoupper($_POST['MRPDemandType'])) . "',
						'" . $_POST['Description'] . "'
						)";
		$Msg = __('The new demand type has been added to the database');
	}

	if ($InputError !=1){
		$Result = DB_query($SQL,__('The update/addition of the demand type failed because'));
		prnMsg($Msg,'success');
		unset ($_POST['Description']);
		unset ($_POST['MRPDemandType']);
		unset ($SelectedDT);
	}

} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM mrpdemands
	         WHERE mrpdemands.mrpdemandtype='" . $SelectedDT . "'
	         GROUP BY mrpdemandtype";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this demand type because MRP Demand records exist for this type') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' .__('MRP Demands referring to this type'),'warn');
    } else {
			$SQL="DELETE FROM mrpdemandtypes WHERE mrpdemandtype='" . $SelectedDT . "'";
			$Result = DB_query($SQL);
			prnMsg(__('The selected demand type record has been deleted'),'success');
	}
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=manuf">' . __('Manufacturing') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Demand Types') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="demand-type-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedDT) ? __('Update Demand Type') : __('Create New Type')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Defined Demand Types') . '</h3>
                    </div>';

                    $SQL = "SELECT mrpdemandtype, description FROM mrpdemandtypes";
                    $Result = DB_query($SQL);

                    if (DB_num_rows($Result) > 0) {
                        echo '<div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('Type') . '</th>
                                            <th>' . __('Description') . '</th>
                                            <th style="width: 100px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        while ($MyRow = DB_fetch_row($Result)) {
                            echo '<tr>
                                    <td style="font-weight: 700;">', $MyRow[0], '</td>
                                    <td style="font-size: 0.9rem; color: #64748b;">', $MyRow[1], '</td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SelectedDT=', $MyRow[0], '" class="action-link" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
                                        <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SelectedDT=', $MyRow[0], '&amp;delete=yes" class="action-link delete" style="margin-left: 15px;" onclick="return confirm(\'' . __('Remove this demand type?') . '\')" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>';
                        }
                        echo '      </tbody>
                                </table>
                            </div>';
                    } else {
                        echo '<div class="db-card-body" style="text-align: center; color: #64748b; padding: 40px;">' . __('No demand types defined.') . '</div>';
                    }
echo '          </div>';
                
                if (isset($SelectedDT) and !isset($_GET['delete'])) {
                    echo '<div class="centre" style="margin-bottom: 20px;">
                            <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" class="architect-btn" style="background: #f3f4f6; color: #4b5563; box-shadow: none;">' . __('Show all Demand Types') . '</a>
                          </div>';
                }

echo '      </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="demand-type-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

                    if (isset($SelectedDT) and !isset($_GET['delete'])) {
                        $SQL = "SELECT mrpdemandtype, description FROM mrpdemandtypes WHERE mrpdemandtype='" . $SelectedDT . "'";
                        $Result = DB_query($SQL);
                        $MyRow = DB_fetch_array($Result);

                        $_POST['MRPDemandType'] = $MyRow['mrpdemandtype'];
                        $_POST['Description'] = $MyRow['description'];

                        echo '<input type="hidden" name="SelectedDT" value="' . $SelectedDT . '" />
                              <input type="hidden" name="MRPDemandType" value="' . $_POST['MRPDemandType'] . '" />';
                        echo '<div class="db-card" style="border-color: #059669;">
                                <div class="db-card-header" style="background: #f0fdf4;">
                                    <h3 class="db-card-title"><i class="fas fa-edit"></i> ' . __('Edit Settings') . '</h3>
                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" style="color: #64748b;"><i class="fas fa-times"></i></a>
                                </div>
                                <div class="db-card-body">
                                    <field>
                                        <label>' . __('Demand Type Code') . '</label>
                                        <div class="fieldtext">' . $_POST['MRPDemandType'] . '</div>
                                    </field>';
                    } else {
                        if (!isset($_POST['MRPDemandType'])) $_POST['MRPDemandType'] = '';
                        echo '<div class="db-card">
                                <div class="db-card-header">
                                    <h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Quick Create') . '</h3>
                                </div>
                                <div class="db-card-body">
                                    <field>
                                        <label for="MRPDemandType">' . __('Type Code') . '</label>
                                        <input type="text" name="MRPDemandType" size="6" maxlength="5" placeholder="e.g. FCAST" value="' . $_POST['MRPDemandType'] . '" />
                                    </field>';
                    }

                    if (!isset($_POST['Description'])) $_POST['Description'] = '';
                    echo '<field>
                            <label for="Description">' . __('Description') . '</label>
                            <input type="text" name="Description" size="31" maxlength="30" placeholder="' . __('Briefly describe this type...') . '" value="' . $_POST['Description'] . '" />
                        </field>
                        
                        <div style="margin-top: 10px;">
                            <button type="submit" name="submit" class="architect-btn" style="width: 100%;">
                                <i class="fas fa-save"></i> ' . (isset($SelectedDT) ? __('Commit Changes') : __('Register New Type')) . '
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="db-card" style="background: #f8fafc; border-style: dashed;">
                    <div class="db-card-body" style="padding: 15px;">
                        <h4 style="font-size: 0.7rem; font-weight: 800; color: #475569; margin: 0 0 8px 0; text-transform: uppercase;">' . __('About Demand Types') . '</h4>
                        <p style="font-size: 0.75rem; color: #64748b; line-height: 1.5; margin: 0;">' . __('Demand types allow you to categorize different sources of product requirements (e.g., Forecasts, Safety Stock) within the MRP calculation.') . '</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
