<?php

// Maintains the calendar of valid manufacturing dates for MRP

require(__DIR__ . '/includes/session.php');

$Title = __('MRP Calendar');
$ViewTopic = 'MRP';
$BookMark = 'MRP_Calendar';

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
    .db-card-body { padding: 20px; }
	
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
    field input[type="date"], field input[type="text"] {
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }

    .exclude-days-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }
    .check-item {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        cursor: pointer; transition: all 0.2s;
    }
    .check-item:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .check-item input { margin: 0; width: 18px; height: 18px; cursor: pointer; }
    .check-item span { font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase; }
    
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
        grid-template-columns: 1fr 380px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 500px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }

    @media (max-width: 1024px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}
if (isset($_POST['ChangeDate'])){$_POST['ChangeDate'] = ConvertSQLDate($_POST['ChangeDate']);}

if (isset($_POST['ChangeDate'])){
	$ChangeDate =trim(mb_strtoupper($_POST['ChangeDate']));
} elseif (isset($_GET['ChangeDate'])){
	$ChangeDate =trim(mb_strtoupper($_GET['ChangeDate']));
}

if (isset($_POST['submit'])) {
	submit($ChangeDate);
} elseif (isset($_POST['update'])) {
	update($ChangeDate);
}

// Layout Wrapper Start
echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div style="font-size: 0.6rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">
						<i class="fas fa-industry"></i> ' . __('Manufacturing') . ' <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> ' . __('Production Planning') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="calendar-form" name="submit" class="architect-btn">
                        <i class="fas fa-magic"></i> ' . __('Generate Calendar') . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
            
            if (isset($_POST['ListAll'])) {
                ShowDays();
            } else {
                echo '<div class="db-card">
                        <div class="db-card-body" style="text-align: center; padding: 60px; color: #64748b;">
                            <i class="fas fa-calendar-alt" style="font-size: 3rem; opacity: 0.2; margin-bottom: 20px;"></i>
                            <h3 style="margin: 0; color: #1e293b;">' . __('Calendar Workspace') . '</h3>
                            <p style="margin-top: 10px;">' . __('Use the generation tool on the right to build or list the manufacturing dates.') . '</p>
                        </div>
                      </div>';
            }

echo '      </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                ShowInputForm($ChangeDate);
echo '      </aside>
        </div>
    </div>';

function submit(&$ChangeDate)
{
	$InputError = 0;
	if (!Is_Date($_POST['FromDate'])) {
		$InputError = 1;
		prnMsg(__('Invalid From Date'),'error');
	}
	if (!Is_Date($_POST['ToDate'])) {
		$InputError = 1;
		prnMsg(__('Invalid To Date'),'error');
	}

	$FormatFromDate = FormatDateForSQL($_POST['FromDate']);
	$FormatToDate = FormatDateForSQL($_POST['ToDate']);
	$ConvertFromDate = ConvertSQLDate($FormatFromDate);
	$ConvertToDate = ConvertSQLDate($FormatToDate);

	$DateDiff = DateDiff($ConvertToDate,$ConvertFromDate,'d');

	if ($DateDiff < 1) {
		$InputError = 1;
		prnMsg(__('To Date Must Be Greater Than From Date'),'error');
	}

	 if ($InputError == 1) {
		return;
	 }

	$SQL = "DROP TABLE IF EXISTS mrpcalendar";
	$Result = DB_query($SQL);

	$SQL = "CREATE TABLE mrpcalendar (
				calendardate date NOT NULL,
				daynumber int(6) NOT NULL,
				manufacturingflag smallint(6) NOT NULL default '1',
				INDEX (daynumber),
				PRIMARY KEY (calendardate)) DEFAULT CHARSET=utf8";
	$ErrMsg = __('The SQL to create passbom failed with the message');
	$Result = DB_query($SQL, $ErrMsg);

	$DaysTextArray = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	$ExcludeDays = array($_POST['Sunday']??'',$_POST['Monday']??'',$_POST['Tuesday']??'',$_POST['Wednesday']??'',
						 $_POST['Thursday']??'',$_POST['Friday']??'',$_POST['Saturday']??'');

	$CalDate = $ConvertFromDate;
	for ($i = 0; $i <= $DateDiff; $i++) {
		 $DateAdd = FormatDateForSQL(DateAdd($CalDate,'d',$i));
		 $DayOfWeek = DayOfWeekFromSQLDate($DateAdd);
		 $ManuFlag = 1;
		 foreach ($ExcludeDays as $exday) {
			 if ($exday == $DaysTextArray[$DayOfWeek]) {
				 $ManuFlag = 0;
			 }
		 }

		 $SQL = "INSERT INTO mrpcalendar (
					calendardate,
					daynumber,
					manufacturingflag)
				 VALUES ('" . $DateAdd . "',
						'1',
						'" . $ManuFlag . "')";
		$Result = DB_query($SQL, $ErrMsg);
	}

	$DayNumber = 1;
	$SQL = "SELECT * FROM mrpcalendar ORDER BY calendardate";
	$Result = DB_query($SQL, $ErrMsg);
	while ($MyRow = DB_fetch_array($Result)) {
		   if ($MyRow['manufacturingflag'] == "1") {
			   $DayNumber++;
		   }
		   $CalDate = $MyRow['calendardate'];
		   $SQL = "UPDATE mrpcalendar SET daynumber = '" . $DayNumber . "'
					WHERE calendardate = '" . $CalDate . "'";
		   $Resultupdate = DB_query($SQL, $ErrMsg);
	}
	prnMsg(__('The MRP Calendar has been created'),'success');
}

function update(&$ChangeDate)
{
	$InputError = 0;
	$CalDate = FormatDateForSQL($ChangeDate);
	$SQL="SELECT COUNT(*) FROM mrpcalendar
		  WHERE calendardate='$CalDate'
		  GROUP BY calendardate";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] < 1  ||  !Is_Date($ChangeDate))  {
		$InputError = 1;
		prnMsg(__('Invalid Change Date'),'error');
	}

	 if ($InputError == 1) {
		return;
	 }

	$SQL="SELECT mrpcalendar.* FROM mrpcalendar WHERE calendardate='$CalDate'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	$NewManufacturingFlag = ($MyRow[2] == 0) ? 1 : 0;
    
	$SQL = "UPDATE mrpcalendar SET manufacturingflag = '".$NewManufacturingFlag."'
			WHERE calendardate = '".$CalDate."'";
	$ErrMsg = __('Cannot update the MRP Calendar');
	$Resultupdate = DB_query($SQL, $ErrMsg);
	prnMsg(__('The MRP calendar record for') . ' ' . $ChangeDate  . ' ' . __('has been updated'),'success');
	unset ($ChangeDate);

	$DayNumber = 1;
	$SQL = "SELECT * FROM mrpcalendar ORDER BY calendardate";
	$Result = DB_query($SQL, $ErrMsg);
	while ($MyRow = DB_fetch_array($Result)) {
		   if ($MyRow['manufacturingflag'] == '1') {
			   $DayNumber++;
		   }
		   $CalDate = $MyRow['calendardate'];
		   $SQL = "UPDATE mrpcalendar SET daynumber = '" . $DayNumber . "'
					WHERE calendardate = '" . $CalDate . "'";
		   $Resultupdate = DB_query($SQL, $ErrMsg);
	}
}

function ShowDays() 
{
	$FromDate = FormatDateForSQL($_POST['FromDate']);
	$ToDate = FormatDateForSQL($_POST['ToDate']);
	$SQL = "SELECT calendardate,
				   daynumber,
				   manufacturingflag,
				   DAYNAME(calendardate) as dayname
			FROM mrpcalendar
			WHERE calendardate >='" . $FromDate . "'
			AND calendardate <='" . $ToDate . "'
            ORDER BY calendardate";

	$ErrMsg = __('The SQL to find the parts selected failed with the message');
	$Result = DB_query($SQL, $ErrMsg);

    echo '<div class="db-card">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-calendar-day"></i> ' . __('Manufacturing Schedule') . '</h3>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>' . __('Date') . '</th>
                            <th>' . __('Day') . '</th>
                            <th>' . __('Auth to Manufacture') . '</th>
                        </tr>
                    </thead>
                    <tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		$flag = $MyRow['manufacturingflag'] == 1 ? '<span style="color:#059669; font-weight:800;">' . __('Enabled') . '</span>' : '<span style="color:#dc2626; opacity:0.6;">' . __('Closed') . '</span>';
		echo '<tr>
				<td style="font-weight:700;">', ConvertSQLDate($MyRow[0]), '</td>
				<td style="font-size:0.8rem; color:#64748b;">', __($MyRow[3]), '</td>
				<td>', $flag, '</td>
			</tr>';
	}
	echo '      </tbody>
                </table>
            </div>
        </div>';
	unset ($ChangeDate);
}

function ShowInputForm(&$ChangeDate)
{
	if (!isset($_POST['FromDate'])) {
		$_POST['FromDate']=date($_SESSION['DefaultDateFormat']);
		$_POST['ToDate']=date($_SESSION['DefaultDateFormat']);
	}
	echo '<form id="calendar-form" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
	        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-cogs"></i> ' . __('Generation Tool') . '</h3>
			</div>
            <div class="db-card-body">
                <field>
                    <label for="FromDate">' . __('Effective Start') . '</label>
                    <input type="date" name="FromDate" required value="' . FormatDateForSQL($_POST['FromDate']) . '" />
                </field>
                <field>
                    <label for="ToDate">' . __('Effective End') . '</label>
                    <input type="date" name="ToDate" required value="' . FormatDateForSQL($_POST['ToDate']) . '" />
                </field>
                
                <h4 style="font-size: 0.65rem; font-weight: 850; color: #64748b; margin: 25px 0 12px 0; text-transform: uppercase;">' . __('Exclude Closed Days') . '</h4>
                <div class="exclude-days-grid">';
                    $days = ['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
                    foreach($days as $day) {
                        echo '<label class="check-item">
                                <input type="checkbox" name="' . $day . '" value="' . $day . '" />
                                <span>' . __($day) . '</span>
                              </label>';
                    }
    echo '      </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <button type="submit" name="submit" class="architect-btn" style="padding: 10px;">' . __('Generate') . '</button>
                    <button type="submit" name="ListAll" class="architect-btn" style="background:#f3f4f6; color:#4b5563; padding: 10px; box-shadow:none;">' . __('List Range') . '</button>
                </div>
            </div>
          </div>';

	if (!isset($_POST['ChangeDate'])) {
		$_POST['ChangeDate']=date($_SESSION['DefaultDateFormat']);
	}

	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-edit"></i> ' . __('Status Override') . '</h3>
			</div>
            <div class="db-card-body">
                <field>
                    <label>' . __('Target Date') . '</label>
                    <input name="ChangeDate" type="date" value="' . FormatDateForSQL($_POST['ChangeDate']) . '" />
                </field>
                <button type="submit" name="update" class="architect-btn" style="width: 100%;">
                    <i class="fas fa-toggle-on"></i> ' . __('Toggle Status') . '
                </button>
            </div>
          </div>
          </form>';
}

include(__DIR__ . '/includes/footer.php');
