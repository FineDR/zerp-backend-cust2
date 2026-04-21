<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<form name="reporthome" method="post" action="ReportCreator.php?action=step2">
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
    
    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-list-check"></i> <?php echo __('Dashboard Actions'); ?></h3>
            <div style="display:flex; gap:12px;">
                <button name="todo" type="submit" value="<?php echo RPT_BTN_ADDNEW; ?>" class="arch-btn">
                    <i class="fas fa-plus"></i> <?php echo RPT_BTN_ADDNEW; ?>
                </button>
                <button name="todo" type="submit" value="<?php echo RPT_BTN_IMPORT; ?>" class="arch-btn arch-btn-secondary">
                    <i class="fas fa-file-import"></i> <?php echo RPT_BTN_IMPORT; ?>
                </button>
            </div>
        </div>
        
        <div style="padding:24px;">
            <?php echo $DropDownString; ?>
        </div>

        <div class="arch-card-header" style="border-top:1px solid #f1f5f9; border-bottom:0; background:#f8fafc;">
            <h3 class="arch-card-title" style="font-size:0.75rem; color:#64748b;"><?php echo __('Selected Report Management'); ?></h3>
            <div style="display:flex; gap:10px;">
                <button name="todo" type="submit" value="<?php echo RPT_BTN_EDIT; ?>" class="arch-btn arch-btn-secondary" style="background:#fff; border:1px solid #e2e8f0;">
                    <i class="fas fa-pen-to-square"></i> <?php echo RPT_BTN_EDIT; ?>
                </button>
                <button name="todo" type="submit" value="<?php echo RPT_BTN_RENAME; ?>" class="arch-btn arch-btn-secondary" style="background:#fff; border:1px solid #e2e8f0;">
                    <i class="fas fa-font"></i> <?php echo RPT_BTN_RENAME; ?>
                </button>
                <button name="todo" type="submit" value="<?php echo RPT_BTN_COPY; ?>" class="arch-btn arch-btn-secondary" style="background:#fff; border:1px solid #e2e8f0;">
                    <i class="fas fa-copy"></i> <?php echo RPT_BTN_COPY; ?>
                </button>
                <button name="todo" type="submit" value="<?php echo RPT_BTN_EXPORT; ?>" class="arch-btn arch-btn-secondary" style="background:#fff; border:1px solid #e2e8f0;">
                    <i class="fas fa-file-export"></i> <?php echo RPT_BTN_EXPORT; ?>
                </button>
                <button name="todo" type="submit" value="<?php echo RPT_BTN_DEL; ?>" class="arch-btn" style="background:#ef4444;" onClick="return confirm('<?php echo RPT_REPDEL; ?>')">
                    <i class="fas fa-trash-can"></i> <?php echo RPT_BTN_DEL; ?>
                </button>
            </div>
        </div>
    </div>
</form>
