<?php

require(__DIR__ . '/includes/session.php');

use PHPMailer\PHPMailer\PHPMailer;

$Title = __('SMTP Server Settings');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'SMTPServer';

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
        max-width: 1200px;
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
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 16px 20px;
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
    .db-card-body { padding: 30px; }
	
    field {
        display: block;
        margin-bottom: 24px;
    }
    field label {
        font-size: 0.62rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 8px;
        opacity: 0.8;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 46px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 16px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    .fieldhelp { font-size: 0.75rem; color: #64748b; margin-top: 8px; display: block; font-weight: 500; font-style: italic; }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 14px 28px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.9rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }

    .db-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    .input-group-grid { display: grid; grid-template-columns: 1fr 120px; gap: 20px; }

    @media (max-width: 768px) {
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .input-group-grid { grid-template-columns: 1fr; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['submit'])) {
	$Connection = 0;
	if ($_POST['MailServerSetting'] == 1 || $_POST['MailServerSetting'] == 0) {
		$mail = new PHPMailer(true);
		$mail->SMTPAuth = true;
		$mail->Username = $_POST['UserName'];
		$mail->Password = $_POST['Password'];
		$mail->Host = $_POST['Host'];
		$mail->Port = $_POST['Port'];
		try { $Connection = $mail->SmtpConnect(); } catch(Exception $error) { prnMsg(__('The connection to the SMPT server failed'), 'error'); }
		if ($Connection == 1) { prnMsg(__('Connection successful!'), 'success'); }
	}

	if ($_POST['MailServerSetting'] == 1) {
		$SQL="UPDATE emailsettings SET host='".$_POST['Host']."', port='".$_POST['Port']."', heloaddress='".$_POST['HeloAddress']."', username='".$_POST['UserName']."', password='".$_POST['Password']."', auth='".$_POST['Auth']."'";
		$Msg = __('The settings for the SMTP server have been successfully updated');
	} else {
		$SQL = "INSERT INTO emailsettings(host, port, heloaddress, username, password, auth) VALUES ('".$_POST['Host']."', '".$_POST['Port']."', '".$_POST['HeloAddress']."', '".$_POST['UserName']."', '".$_POST['Password']."', '".$_POST['Auth']."')";
		$Msg = __('The settings for the SMTP server have been successfully inserted');
	}
	DB_query($SQL);
	prnMsg($Msg, 'success');
}

$SQL="SELECT * FROM emailsettings";
$Result = DB_query($SQL);
if (DB_num_rows($Result)!=0){
	$MailServerSetting = 1; $MyRow=DB_fetch_array($Result);
} else {
	$MailServerSetting = 0; $MyRow=['host'=>'','port'=>'','heloaddress'=>'','username'=>'','password'=>'','timeout'=>5,'auth'=>0];
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=system">' . __('Setup') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('SMTP Parameters') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="smtp-form" name="submit" class="architect-btn">
                        <i class="fas fa-paper-plane"></i> ' . __('Update Credentials') . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-card">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-server"></i> ' . __('Outgoing Email Configuration') . '</h3>
            </div>
            <div class="db-card-body">
                <form id="smtp-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <input type="hidden" name="MailServerSetting" value="' . $MailServerSetting . '" />
                    
                    <div class="input-group-grid">
                        <field>
                            <label>' . __('Server Host Name') . '</label>
                            <input type="text" name="Host" required value="' . $MyRow['host'] . '" placeholder="e.g. smtp.gmail.com" autofocus />
                            <span class="fieldhelp">' . __('The hostname or IP address of your mail server') . '</span>
                        </field>
                        <field>
                            <label>' . __('SMTP Port') . '</label>
                            <input type="text" name="Port" required maxlength="5" value="' . $MyRow['port'].'" placeholder="587" />
                            <span class="fieldhelp">' . __('Common: 25, 465, 587') . '</span>
                        </field>
                    </div>

                    <div class="input-group-grid">
                        <field>
                            <label>' . __('User Name / Email') . '</label>
                            <input type="text" required name="UserName" maxlength="50" value="' . $MyRow['username'] . '" placeholder="account@example.com" />
                        </field>
                        <field>
                            <label>' . __('Password') . '</label>
                            <input type="password" required name="Password" value="' . $MyRow['password'] . '" />
                        </field>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <field>
                            <label>' . __('Authorization Required') . '</label>
                            <select name="Auth">
                                <option value="1" ' . ($MyRow['auth']==1 ? 'selected' : '') . '>' . __('True (Recommended)') . '</option>
                                <option value="0" ' . ($MyRow['auth']==0 ? 'selected' : '') . '>' . __('False') . '</option>
                            </select>
                        </field>
                        <field>
                            <label>' . __('HELO Command') . '</label>
                            <input type="text" name="HeloAddress" value="' . $MyRow['heloaddress'] . '" placeholder="localhost" />
                        </field>
                        <field>
                            <label>' . __('Timeout (Seconds)') . '</label>
                            <input type="number" name="Timeout" value="' . $MyRow['timeout'] . '" />
                        </field>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: center;">
                        <button type="submit" name="submit" class="architect-btn" style="min-width: 250px;">
                            <i class="fas fa-sync-alt"></i> ' . __('Test & Save Settings') . '
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div style="max-width: 800px; margin: 0 auto; color: #64748b; font-size: 0.8rem; padding: 0 10px;">
            <p><i class="fas fa-info-circle"></i> ' . __('Note: The system uses PHPMailer to establish a secure SSL/TLS connection. Ensure your firewall allows outbound traffic on the specified port.') . '</p>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
