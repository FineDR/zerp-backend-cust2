<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<form action="ReportCreator.php?action=step8" method="post" enctype="multipart/form-data" name="reporthome">
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
    <input name="Type" type="hidden" value="<?php echo $Type; ?>">

    <!-- Wizard Action Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <button name="todo" type="submit" value="<?php echo RPT_BTN_BACK; ?>" class="arch-btn arch-btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo RPT_BTN_BACK; ?>
        </button>
        <button name="todo" type="submit" value="<?php echo RPT_BTN_IMPORT; ?>" class="arch-btn">
            <i class="fas fa-file-import"></i> <?php echo RPT_BTN_IMPORT; ?>
        </button>
    </div>

    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-box-archive"></i> <?php echo RPT_RPTIMPORT; ?></h3>
        </div>
        
        <div style="padding:24px;">
            <div style="margin-bottom:24px;">
                <label class="arch-form-label"><?php echo RPT_DEFIMP; ?></label>
                <div style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                    <select name="RptFileName" size="10" class="arch-form-input" style="height:auto; border:none; padding:10px;">
                        <?php echo ReadDefReports(); ?>
                    </select>
                </div>
                <p style="font-size:0.75rem; color:#64748b; margin-top:8px; font-weight:500;">
                    <i class="fas fa-info-circle"></i> <?php echo __('Select a pre-defined system report to import into your custom library.'); ?>
                </p>
            </div>

            <div style="background:#f8fafc; border-radius:12px; padding:20px; border:1px solid #f1f5f9;">
                <h4 style="font-size:0.75rem; font-weight:800; color:var(--primary); text-transform:uppercase; margin:0 0 16px 0;">
                    <?php echo __('External File Import'); ?>
                </h4>

                <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:24px;">
                    <div>
                        <label class="arch-form-label"><?php echo RPT_RPTBROWSE; ?></label>
                        <input type="file" name="reportfile" class="arch-form-input" style="padding-top:10px;">
                    </div>
                    <div>
                        <label class="arch-form-label"><?php echo RPT_RPTENTER; ?></label>
                        <input name="reportname" type="text" class="arch-form-input" 
                               value="<?php echo $ReportName; ?>" placeholder="<?php echo RPT_RPTNOENTER; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
