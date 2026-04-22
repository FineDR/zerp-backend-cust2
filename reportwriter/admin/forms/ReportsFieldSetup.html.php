<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<!-- Modernized Wizard Action Bar -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <form method="post" action="ReportCreator.php?action=step6">
        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
        <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
        <input name="Type" type="hidden" value="<?php echo $Type; ?>">
        <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
        <button name="todo" type="submit" value="<?php echo RPT_BTN_BACK; ?>" class="arch-btn arch-btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo RPT_BTN_BACK; ?>
        </button>
    </form>
    <form method="post" action="ReportCreator.php?action=step6">
        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
        <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
        <input name="Type" type="hidden" value="<?php echo $Type; ?>">
        <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
        <button name="todo" type="submit" value="<?php echo RPT_BTN_CONT; ?>" class="arch-btn">
            <?php echo RPT_BTN_CONT; ?> <i class="fas fa-arrow-right"></i>
        </button>
    </form>
</div>

<div style="display:grid; grid-template-columns:1fr; gap:24px;">
    
    <!-- Field Entry Form Card -->
    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title">
                <i class="fas fa-plus-circle"></i> 
                <?php echo ($FieldListings['defaults']['buttonvalue']=='Change' ? __('Modify Field') : RPT_ENTRFLD); ?>
            </h3>
        </div>
        <div style="padding:24px;">
            <form name="RptFieldForm1" method="post" action="ReportCreator.php?action=step6">
                <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                <input name="ReportID" type="hidden" value="<?php echo $ReportID ?>">
                <input name="Type" type="hidden" value="<?php echo $Type; ?>">
                <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">

                <div style="display:grid; grid-template-columns:100px 1fr 1fr 150px; gap:20px; align-items:end;">
                    <div>
                        <label class="arch-form-label"><?php echo RPT_ORDER; ?></label>
                        <?php if ($FieldListings['defaults']['buttonvalue']=='Change') { ?>
                            <input name="SeqNum" type="hidden" value="<?php echo $FieldListings['defaults']['seqnum']; ?>">
                            <div style="font-weight:900; font-size:1.2rem; color:var(--primary); height:48px; display:flex; align-items:center;"><?php echo $FieldListings['defaults']['seqnum']; ?></div>
                        <?php } else { ?>
                            <input name="SeqNum" type="number" class="arch-form-input" value="<?php echo $FieldListings['defaults']['seqnum']; ?>" placeholder="...">
                        <?php } ?>
                    </div>

                    <?php if ($Type<>'frm') { ?>
                        <div>
                            <label class="arch-form-label"><?php echo RPT_TBLFNAME; ?></label>
                            <select name="FieldName" class="arch-form-input" style="height:48px;">
                                <option value=""><?php echo RPT_SELECT; ?></option>
                                <?php echo CreateFieldList($ReportID, $FieldListings['defaults']['fieldname'],''); ?>
                            </select>
                        </div>
                    <?php } ?>

                    <div>
                        <label class="arch-form-label"><?php echo RPT_DISPNAME; ?></label>
                        <input name="DisplayDesc" type="text" class="arch-form-input" value="<?php echo $FieldListings['defaults']['displaydesc']; ?>" placeholder="...">
                    </div>

                    <div style="display:flex; justify-content:center;">
                        <button name="todo" type="submit" value="<?php echo $FieldListings['defaults']['buttonvalue']; ?>" class="arch-btn" style="width:100%;">
                            <i class="<?php echo ($FieldListings['defaults']['buttonvalue']=='Change' ? 'fas fa-check' : 'fas fa-plus'); ?>"></i> <?php echo $FieldListings['defaults']['buttonvalue']; ?>
                        </button>
                    </div>
                </div>

                <div style="display:flex; gap:32px; margin-top:20px; padding:15px; background:#f8fafc; border-radius:12px; border:1px solid #f1f5f9;">
                    <?php if ($Type<>'frm') { ?>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:700; color:#475569;">
                            <input name="ColumnBreak" type="checkbox" value="1" <?php echo ($FieldListings['defaults']['columnbreak']=='1' ? 'checked' : ''); ?> style="width:18px; height:18px; accent-color:var(--primary);">
                            <?php echo RPT_BREAK; ?>
                        </label>
                    <?php } ?>
                    
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:700; color:#475569;">
                        <input name="Visible" type="checkbox" value="1" <?php echo ($FieldListings['defaults']['visible']=='1' ? 'checked' : ''); ?> style="width:18px; height:18px; accent-color:var(--primary);" checked>
                        <?php echo RPT_SHOW; ?>
                    </label>

                    <div style="flex-grow:1; display:flex; align-items:center; gap:12px;">
                        <label class="arch-form-label" style="margin:0;"><?php if ($Type=='frm') echo RPT_TYPE; else echo RPT_TOTAL; ?></label>
                        <select name="Params" class="arch-form-input" style="height:36px; font-size:0.75rem;">
                            <?php if ($Type=='frm') {
                                foreach($FormEntries as $key=>$value) {
                                    echo '<option value="'.$key.'" '.($FieldListings['defaults']['params']==$key ? 'selected' : '').'>'.$value.'</option>';
                                }
                            } else {
                                foreach($TotalLevels as $key=>$value) {
                                    echo '<option value="'.$key.'" '.($FieldListings['defaults']['params']==$key ? 'selected' : '').'>'.$value.'</option>';
                                }
                            } ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Field Registry Card -->
    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-table-columns"></i> <?php echo RPT_FLDLIST; ?></h3>
            <span class="badge badge-system" style="padding:6px 12px;"><?php echo count($FieldListings['lists'] ?? []); ?> <?php echo __('Fields Defined'); ?></span>
        </div>
        <div style="overflow-x:auto;">
            <table class="smooth-table">
                <thead>
                    <tr>
                        <th style="width:60px; text-align:center;"><?php echo RPT_ORDER; ?></th>
                        <?php if ($Type<>'frm') echo '<th>'.RPT_TBLFNAME.'</th>'; ?>
                        <th><?php echo RPT_DISPNAME; ?></th>
                        <?php if ($Type<>'frm') echo '<th style="text-align:center;">'.RPT_BREAK.'</th>'; ?>
                        <th style="text-align:center;"><?php echo RPT_SHOW; ?></th>
                        <th><?php if ($Type=='frm') echo RPT_TYPE; else echo RPT_TOTAL; ?></th>
                        <th style="text-align:right;"><?php echo __('Control'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!isset($FieldListings['lists'])) {
                        echo '<tr><td colspan="7" style="padding:40px; text-align:center; color:#94a3b8; font-weight:600;">
                                <i class="fas fa-ghost" style="display:block; font-size:2rem; margin-bottom:12px; opacity:0.3;"></i>
                                '.RPT_NOFIELD.'
                              </td></tr>';
                    } else {
                        foreach ($FieldListings['lists'] as $FieldDetails) { ?>
                            <tr class="<?php echo ($FieldListings['defaults']['seqnum'] == $FieldDetails['seqnum'] ? 'active-row' : ''); ?>">
                                <form method="post" action="ReportCreator.php?action=step6">
                                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                                    <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
                                    <input name="Type" type="hidden" value="<?php echo $Type; ?>">
                                    <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
                                    <input name="SeqNum" type="hidden" value="<?php echo $FieldDetails['seqnum']; ?>">
                                    <input name="FieldName" type="hidden" value="<?php echo $FieldDetails['fieldname']; ?>">
                                    <input name="DisplayDesc" type="hidden" value="<?php echo $FieldDetails['displaydesc']; ?>">
                                    <?php if ($Type<>'frm') echo '<input name="ColumnBreak" type="hidden" value="'.$FieldDetails['columnbreak'].'">'; ?>
                                    <input name="Visible" type="hidden" value="<?php echo $FieldDetails['visible']; ?>">
                                    <input name="Params" type="hidden" value="<?php echo $FieldDetails['params']; ?>">
                                    
                                    <td align="center" style="font-weight:900; color:var(--primary);"><?php echo $FieldDetails['seqnum']; ?></td>
                                    <?php if ($Type<>'frm') echo '<td style="font-family:monospace; font-size:0.75rem; color:#475569;">'.$FieldDetails['fieldname'].'</td>' ?>
                                    <td style="font-weight:700; color:#1e293b;"><?php echo $FieldDetails['displaydesc']; ?></td>
                                    <?php if ($Type<>'frm') {
                                        echo '<td align="center"><i class="fas '.($FieldDetails['columnbreak']=='1' ? 'fa-check-circle' : 'fa-circle-xmark').'" style="color:'.($FieldDetails['columnbreak']=='1' ? 'var(--primary)' : '#cbd5e1').';"></i></td>';
                                    } ?>
                                    <td align="center"><i class="fas <?php echo ($FieldDetails['visible']=='1' ? 'fa-eye' : 'fa-eye-slash'); ?>" style="color:<?php echo ($FieldDetails['visible']=='1' ? 'var(--primary)' : '#cbd5e1'); ?>;"></i></td>
                                    <td>
                                        <span class="badge" style="background:#f1f5f9; color:#475569;">
                                            <?php if ($Type=='frm') {
                                                $Temp = unserialize($FieldDetails['params']);
                                                echo $FormEntries[$Temp['index'] ?? ''];
                                            } else {
                                                echo $TotalLevels[$FieldDetails['params']];
                                            } ?>
                                        </span>
                                    </td>
                                    <td align="right">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <button type="submit" name="up" class="action-link" title="<?php echo __('Move Up'); ?>"><i class="fas fa-chevron-up"></i></button>
                                            <button type="submit" name="dn" class="action-link" title="<?php echo __('Move Down'); ?>"><i class="fas fa-chevron-down"></i></button>
                                            <button type="submit" name="ed" class="action-link" title="<?php echo __('Edit'); ?>" style="background:#ecfdf5; color:var(--primary);"><i class="fas fa-pen-to-square"></i></button>
                                            <?php if ($Type=='frm') { echo '<button type="submit" name="todo" value="'.RPT_BTN_PROP.'" class="action-link" title="'.RPT_BTN_PROP.'" style="background:#e0f2fe; color:#0284c7;"><i class="fas fa-sliders"></i></button>'; } ?>
                                            <button type="submit" name="rm" class="action-link" title="<?php echo __('Delete'); ?>" style="background:#fef2f2; color:#ef4444;" onClick="return confirm('Delete this field?')"><i class="fas fa-trash-can"></i></button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
