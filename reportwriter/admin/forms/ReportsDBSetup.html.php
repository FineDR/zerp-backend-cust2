<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<form name="DBPageSetup" method="post" action="ReportCreator.php?action=step5">
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
	<input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
	<input name="Type" type="hidden" value="<?php echo $Type; ?>">
	<input name="ReportName" type="hidden" value="<?php echo $myrow['reportname']; ?>">

    <!-- Action Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <button name="todo" type="submit" value="<?php echo RPT_BTN_BACK; ?>" class="arch-btn arch-btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo RPT_BTN_BACK; ?>
        </button>
        <div style="display:flex; gap:12px;">
            <button name="todo" type="submit" value="<?php echo RPT_BTN_UPDATE; ?>" class="arch-btn arch-btn-secondary">
                <i class="fas fa-floppy-disk"></i> <?php echo RPT_BTN_UPDATE; ?>
            </button>
            <button name="todo" type="submit" value="<?php echo RPT_BTN_CONT; ?>" class="arch-btn">
                <?php echo RPT_BTN_CONT; ?> <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-database"></i> <?php echo __('Database Relationship Builder'); ?></h3>
        </div>
        
        <div style="overflow-x:auto;">
            <table class="smooth-table">
                <thead>
                    <tr>
                        <th style="padding:15px; background:#f8fafc;"><?php echo __('Priority'); ?></th>
                        <th style="padding:15px; background:#f8fafc;"><?php echo RPT_TBLNAME; ?></th>
                        <th style="padding:15px; background:#f8fafc;"><?php echo RPT_LINKEQ; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Primary Table -->
                    <tr>
                        <td style="padding:12px 20px; font-weight:700; color:var(--primary);"><?php echo RPT_PRIMARY; ?></td>
                        <td>
                            <select name="Table1" class="arch-form-input" style="height:38px;">
                                <option value=""><?php echo RPT_SELECT; ?></option>
                                <?php echo CreateTableList($ReportID, 1); ?>
                            </select>
                        </td>
                        <td><div style="font-size:0.75rem; color:#94a3b8; font-weight:600;"><i class="fas fa-lock"></i> <?php echo __('Root Table'); ?></div></td>
                    </tr>

                    <?php
                    $TableLabels = [
                        2 => RPT_SECOND,
                        3 => RPT_THIRD,
                        4 => RPT_FOURTH,
                        5 => RPT_FIFTH,
                        6 => RPT_SIXTH
                    ];

                    foreach ($TableLabels as $i => $label) {
                        $tbl_key = 'table'.$i;
                        $crit_key = 'table'.$i.'criteria';
                        $prev_tbl_key = 'table'.($i-1);
                        
                        echo '<tr>
                                <td style="padding:12px 20px; font-weight:700; color:#475569;">' . $label . '</td>
                                <td>
                                    <select name="Table'.$i.'" class="arch-form-input" style="height:38px;">
                                        <option value="">' . RPT_SELECT . '</option>
                                        ' . ($myrow[$prev_tbl_key] ? CreateTableList($ReportID, $i) : '') . '
                                    </select>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <i class="fas fa-link" style="color:#cbd5e1; font-size:0.75rem;"></i>
                                        <input name="Table'.$i.'Criteria" type="text" class="arch-form-input" 
                                               value="'.$myrow[$crit_key].'" style="height:38px; font-family:monospace; font-size:0.8rem;"
                                               placeholder="e.g. table1.field = table2.field">
                                    </div>
                                </td>
                              </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div style="padding:20px; background:#f8fafc; border-top:1px solid #f1f5f9;">
            <p style="font-size:0.75rem; color:#64748b; margin:0; font-weight:500;">
                <i class="fas fa-lightbulb" style="color:var(--primary);"></i> 
                <?php echo __('Tables are linked using INNER JOINs. Ensure your criteria correctly identifies the relationship between primary keys.'); ?>
            </p>
        </div>
    </div>
</form>
