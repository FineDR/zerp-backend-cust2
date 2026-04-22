<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<form name="reportidform" method="post" action="ReportCreator.php?action=step3">
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
    <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
    <input name="Type" type="hidden" value="<?php echo $Type; ?>">
    <input name="ReplaceReportID" type="hidden" value="<?php echo $ReplaceReportID; ?>">

    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-id-card"></i> <?php echo $FormParams['heading']; ?></h3>
        </div>
        
        <div style="padding:24px;">
            <div style="margin-bottom:24px;">
                <label class="arch-form-label"><?php if (isset($Type) and $Type=='frm') echo FRM_RPTENTER; else echo RPT_RPTENTER; ?></label>
                <input name="ReportName" type="text" class="arch-form-input" 
                       value="<?php if (isset($_POST['ReportName'])) echo $_POST['ReportName']; ?>" 
                       placeholder="<?php echo RPT_MAX30; ?>" required>
            </div>

            <?php if ($ReportID=='') { ?>
                <div style="background:#f8fafc; border-radius:12px; padding:20px; border:1px solid #f1f5f9; margin-bottom:24px;">
                    <h4 style="font-size:0.75rem; font-weight:800; color:var(--primary); text-transform:uppercase; margin:0 0 16px 0;">
                        <?php echo RPT_TYPECREATE; ?>
                    </h4>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div class="report-item" style="padding:15px; border:1px solid #e2e8f0; background:#fff;">
                            <input type="radio" name="NewType" value="rpt" checked id="type-rpt">
                            <label for="type-rpt" style="flex-grow:1; cursor:pointer;">
                                <div style="font-weight:700; font-size:0.85rem;"><?php echo RPT_REPORT; ?></div>
                                <div style="font-size:0.7rem; color:#64748b;"><?php echo RPT_RPTGRP; ?></div>
                                <select name="GroupName" class="arch-form-input" style="height:38px; margin-top:8px; font-size:0.8rem;">
                                    <?php foreach($ReportGroups as $key=>$value) echo '<option value="'.$key.'">'.$value.'</option>'; ?>
                                </select>
                            </label>
                        </div>
                        
                        <div class="report-item" style="padding:15px; border:1px solid #e2e8f0; background:#fff;">
                            <input type="radio" name="NewType" value="frm" id="type-frm">
                            <label for="type-frm" style="flex-grow:1; cursor:pointer;">
                                <div style="font-weight:700; font-size:0.85rem;"><?php echo RPT_FORM; ?></div>
                                <div style="font-size:0.7rem; color:#64748b;"><?php echo FRM_RPTGRP; ?></div>
                                <select name="FormGroup" class="arch-form-input" style="height:38px; margin-top:8px; font-size:0.8rem;">
                                    <?php foreach($FormGroups as $key=>$value) echo '<option value="'.$key.'">'.$value.'</option>'; ?>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:24px; border-top:1px solid #f1f5f9;">
                <button name="todo" type="submit" value="<?php echo RPT_BTN_BACK; ?>" class="arch-btn arch-btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo RPT_BTN_BACK; ?>
                </button>
                
                <div style="display:flex; gap:12px;">
                    <?php if (isset($_POST['ReportName']) AND $_POST['todo']<>RPT_BTN_RENAME) { ?>
                        <button name="todo" type="submit" value="<?php echo RPT_BTN_REPLACE; ?>" class="arch-btn" style="background:#ef4444;" onClick="return confirm('<?php echo RPT_REPOVER; ?>')">
                            <i class="fas fa-triangle-exclamation"></i> <?php echo RPT_BTN_REPLACE; ?>
                        </button>
                    <?php } ?>

                    <?php if ($_POST['todo']==RPT_BTN_RENAME) $Button=RPT_BTN_RENAME; else $Button=RPT_BTN_CONT; ?>
                    <button name="todo" type="submit" value="<?php echo $Button; ?>" class="arch-btn">
                        <?php echo $Button; ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
