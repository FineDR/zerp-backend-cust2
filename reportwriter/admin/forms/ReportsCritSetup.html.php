<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<!-- Action Bar -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <form method="post" action="ReportCreator.php?action=step7">
        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>">
        <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
        <input name="Type" type="hidden" value="<?php echo $Type; ?>">
        <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
        <button name="todo" type="submit" value="<?php echo RPT_BTN_BACK; ?>" class="arch-btn arch-btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo RPT_BTN_BACK; ?>
        </button>
    </form>
    <div style="display:flex; gap:12px;">
        <form method="post" action="ReportCreator.php?action=step7">
            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>">
            <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
            <input name="Type" type="hidden" value="<?php echo $Type; ?>">
            <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
            <button name="todo" type="submit" value="<?php echo RPT_BTN_UPDATE; ?>" class="arch-btn arch-btn-secondary">
                <i class="fas fa-floppy-disk"></i> <?php echo RPT_BTN_UPDATE; ?>
            </button>
            <button name="todo" type="submit" value="<?php echo RPT_BTN_FINISH; ?>" class="arch-btn">
                <i class="fas fa-flag-checkered"></i> <?php echo RPT_BTN_FINISH; ?>
            </button>
        </form>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr; gap:24px;">
    
    <!-- Temporal Controls Card -->
    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-calendar-days"></i> <?php echo RPT_DATEINFO; ?></h3>
        </div>
        <div style="padding:24px;">
            <form name="CritFieldForm" method="post" action="ReportCreator.php?action=step7">
                <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>">
                <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
                <input name="Type" type="hidden" value="<?php echo $Type; ?>">
                <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">

                <div style="margin-bottom:20px; font-size:0.8rem; color:#64748b; font-weight:600; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #f1f5f9;">
                    <i class="fas fa-circle-info" style="color:var(--primary);"></i> <?php echo RPT_DATEINST; ?>
                </div>

                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:24px;">
                    <?php 
                    $DateKeys = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x'];
                    foreach ($DateKeys as $idx => $key) {
                        $checked = (mb_strpos($DateListings['displaydesc'], $key) !== false) ? 'checked' : '';
                        echo '<label style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:0.75rem; font-weight:600;">
                                <input type="checkbox" name="DateRange'.($idx+1).'" value="'.$key.'" '.$checked.' style="width:16px; height:16px; accent-color:var(--primary);">
                                '.$DateChoices[$key].'
                              </label>';
                    }
                    ?>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                    <div>
                        <label class="arch-form-label"><?php echo RPT_DATEDEF; ?></label>
                        <select name="DefDate" class="arch-form-input">
                            <?php foreach($DateChoices as $key=>$value) {
                                echo '<option value="'.$key.'" '.($DateListings['params']==$key ? 'selected' : '').'>'.$value.'</option>';
                            } ?>
                        </select>
                    </div>
                    <div>
                        <label class="arch-form-label"><?php echo RPT_DATEFNAME; ?></label>
                        <select name="DateField" class="arch-form-input">
                            <option value=""><?php echo RPT_SLCTFIELD; ?></option>
                            <?php echo CreateFieldList($ReportID, $DateListings['fieldname'], ''); ?>
                        </select>
                    </div>
                </div>

                <?php if ($Type<>'frm') { ?>
                    <div style="margin-top:20px; padding:15px; background:#f0fdf4; border:1px solid #dcfce7; border-radius:12px; display:flex; align-items:center; gap:20px;">
                        <label class="arch-form-label" style="margin:0;"><?php echo RPT_TRUNC; ?></label>
                        <div style="display:flex; gap:20px;">
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.8rem; font-weight:700;">
                                <input type="radio" name="TruncLongDesc" value="1" <?php echo ($TruncListings['params']=='1' ? 'checked' : ''); ?> style="width:18px; height:18px; accent-color:var(--primary);"> <?php echo RPT_YES; ?>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.8rem; font-weight:700;">
                                <input type="radio" name="TruncLongDesc" value="0" <?php echo ($TruncListings['params']=='0' ? 'checked' : ''); ?> style="width:18px; height:18px; accent-color:var(--primary);"> <?php echo RPT_NO; ?>
                            </label>
                        </div>
                    </div>
                <?php } else { ?>
                    <div style="margin-top:20px;">
                        <label class="arch-form-label"><?php echo __('Form Page Break Field'); ?></label>
                        <select name="FormBreakField" class="arch-form-input">
                            <option value=""><?php echo RPT_SLCTFIELD; ?></option>
                            <?php echo CreateFieldList($ReportID, $GroupListings['lists'][0]['fieldname'], ''); ?>
                        </select>
                    </div>
                <?php } ?>
            </form>
        </div>
    </div>

    <?php 
    $RegistrySections = [];
    if ($Type <> 'frm') {
        $RegistrySections[] = [
            'id' => 'grouplist',
            'title' => RPT_GRPLIST,
            'icon' => 'fa-layer-group',
            'data' => $GroupListings
        ];
        $RegistrySections[] = [
            'id' => 'sortlist',
            'title' => RPT_SORTLIST,
            'icon' => 'fa-arrow-down-wide-short',
            'data' => $SortListings
        ];
    }
    $RegistrySections[] = [
        'id' => 'critlist',
        'title' => RPT_BTN_CRIT,
        'icon' => 'fa-filter',
        'data' => $CritListings
    ];

    foreach ($RegistrySections as $Section) {
    ?>
    <div class="arch-card">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas <?php echo $Section['icon']; ?>"></i> <?php echo $Section['title']; ?></h3>
        </div>
        <div style="padding:24px; border-bottom:1px solid #f1f5f9; background:#fafafa;">
            <form method="post" action="ReportCreator.php?action=step7">
                <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                <input name="ReportID" type="hidden" value="<?php echo $ReportID ?>">
                <input name="Type" type="hidden" value="<?php echo $Type; ?>">
                <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
                <input name="EntryType" type="hidden" value="<?php echo $Section['id']; ?>">

                <div style="display:grid; grid-template-columns:100px 1fr 1fr 150px 150px; gap:16px; align-items:end;">
                    <div>
                        <label class="arch-form-label"><?php echo RPT_SEQ; ?></label>
                        <?php if ($Section['data']['defaults']['buttonvalue']=='Change') { ?>
                            <input name="SeqNum" type="hidden" value="<?php echo $Section['data']['defaults']['seqnum']; ?>">
                            <div style="font-weight:900; font-size:1.1rem; color:var(--primary); height:38px; display:flex; align-items:center;"><?php echo $Section['data']['defaults']['seqnum']; ?></div>
                        <?php } else { ?>
                            <input name="SeqNum" type="number" class="arch-form-input" style="height:38px;" value="<?php echo $Section['data']['defaults']['seqnum']; ?>">
                        <?php } ?>
                    </div>
                    <div>
                        <label class="arch-form-label"><?php echo RPT_TBLFNAME; ?></label>
                        <select name="FieldName" class="arch-form-input" style="height:38px;">
                            <option value=""><?php echo RPT_SLCTFIELD; ?></option>
                            <?php echo CreateFieldList($ReportID, $Section['data']['defaults']['fieldname'], ''); ?>
                        </select>
                    </div>
                    <div>
                        <label class="arch-form-label"><?php echo RPT_DISPNAME; ?></label>
                        <input name="DisplayDesc" type="text" class="arch-form-input" style="height:38px;" value="<?php echo $Section['data']['defaults']['displaydesc']; ?>">
                    </div>
                    <div>
                        <label class="arch-form-label"><?php echo ($Section['id'] == 'critlist' ? RPT_CRITTYPE : RPT_DEFAULT); ?></label>
                        <?php if ($Section['id'] == 'critlist') { ?>
                            <select name="Params" class="arch-form-input" style="height:38px; font-size:0.75rem;">
                                <?php foreach($CritChoices as $k=>$v) {
                                    echo '<option value="'.$k.'" '.($Section['data']['defaults']['params']==$k ? 'selected':'').'>'.mb_substr($v,2).'</option>';
                                } ?>
                            </select>
                        <?php } else { ?>
                            <div style="height:38px; display:flex; align-items:center; gap:8px;">
                                <input name="Params" type="checkbox" value="1" style="width:18px; height:18px; accent-color:var(--primary);">
                                <span style="font-size:0.7rem; font-weight:700; color:#64748b;"><?php echo __('Enable Default'); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                    <button name="todo" type="submit" value="<?php echo $Section['data']['defaults']['buttonvalue']; ?>" class="arch-btn" style="height:38px;">
                        <i class="fas <?php echo ($Section['data']['defaults']['buttonvalue']=='Change' ? 'fa-check' : 'fa-plus'); ?>"></i> <?php echo $Section['data']['defaults']['buttonvalue']; ?>
                    </button>
                </div>
            </form>
        </div>
        <div style="overflow-x:auto;">
            <table class="smooth-table">
                <thead>
                    <tr>
                        <th style="width:60px; text-align:center;"><?php echo RPT_SEQ; ?></th>
                        <th><?php echo RPT_TBLFNAME; ?></th>
                        <th><?php echo RPT_DISPNAME; ?></th>
                        <th style="text-align:center;"><?php echo ($Section['id'] == 'critlist' ? RPT_CRITTYPE : RPT_DEFAULT); ?></th>
                        <th style="text-align:right;"><?php echo __('Control'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$Section['data']['lists']) {
                        echo '<tr><td colspan="5" style="padding:30px; text-align:center; color:#94a3b8; font-weight:600;">'.RPT_NOFIELD.'</td></tr>';
                    } else {
                        foreach ($Section['data']['lists'] as $Field) { ?>
                            <tr class="<?php echo ($Section['data']['defaults']['seqnum'] == $Field['seqnum'] ? 'active-row' : ''); ?>">
                                <form method="post" action="ReportCreator.php?action=step7">
                                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                                    <input name="ReportID" type="hidden" value="<?php echo $ReportID ?>">
                                    <input name="Type" type="hidden" value="<?php echo $Type; ?>">
                                    <input name="ReportName" type="hidden" value="<?php echo $reportname; ?>">
                                    <input name="EntryType" type="hidden" value="<?php echo $Section['id']; ?>">
                                    <input name="SeqNum" type="hidden" value="<?php echo $Field['seqnum'] ?>">
                                    <input name="FieldName" type="hidden" value="<?php echo $Field['fieldname'] ?>">
                                    <input name="DisplayDesc" type="hidden" value="<?php echo $Field['displaydesc'] ?>">
                                    <input name="Params" type="hidden" value="<?php echo $Field['params'] ?>">

                                    <td align="center" style="font-weight:900; color:var(--primary); font-size:0.9rem;"><?php echo $Field['seqnum']; ?></td>
                                    <td style="font-family:monospace; font-size:0.75rem; color:#475569;"><?php echo $Field['fieldname']; ?></td>
                                    <td style="font-weight:700; color:#1e293b;"><?php echo $Field['displaydesc']; ?></td>
                                    <td align="center">
                                        <?php if ($Section['id'] == 'critlist') { ?>
                                            <span class="badge" style="background:#f1f5f9; color:#475569;"><?php echo mb_substr($CritChoices[$Field['params']],2); ?></span>
                                        <?php } else { ?>
                                            <i class="fas <?php echo ($Field['params']=='1' ? 'fa-check-circle' : 'fa-circle-xmark'); ?>" style="color:<?php echo ($Field['params']=='1' ? 'var(--primary)' : '#cbd5e1'); ?>;"></i>
                                        <?php } ?>
                                    </td>
                                    <td align="right">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <button type="submit" name="up" class="action-link" title="Up"><i class="fas fa-chevron-up"></i></button>
                                            <button type="submit" name="dn" class="action-link" title="Down"><i class="fas fa-chevron-down"></i></button>
                                            <button type="submit" name="ed" class="action-link" style="background:#ecfdf5; color:var(--primary);"><i class="fas fa-pen-to-square"></i></button>
                                            <button type="submit" name="rm" class="action-link" style="background:#fef2f2; color:#ef4444;" onClick="return confirm(\'Delete this field?\')"><i class="fas fa-trash-can"></i></button>
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
    <?php } ?>
</div>
