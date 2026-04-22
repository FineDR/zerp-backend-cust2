<?php

if (!isset($PathPrefix)) {
	header('Location: ../../../');
	exit();
}

?>

<form name="RptPageSetup" method="post" action="ReportCreator.php?action=step4">
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
	<input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
	<input name="Type" type="hidden" value="<?php echo $Type; ?>">

    <!-- Wizard Action Bar -->
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

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <!-- Page Layout Card -->
        <div class="arch-card">
            <div class="arch-card-header">
                <h3 class="arch-card-title"><i class="fas fa-file-invoice"></i> <?php echo RPT_PGLAYOUT; ?></h3>
            </div>
            <div style="padding:20px;">
                <div style="margin-bottom:20px;">
                    <label class="arch-form-label"><?php echo RPT_PAPER; ?></label>
                    <select name="PaperSize" class="arch-form-input">
                    <?php foreach($PaperSizes as $key=>$value) {
                        if ($myrow['papersize']==$key) $selected = ' selected'; else  $selected = '';
                        echo '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
                    } ?>
                    </select>
                </div>
                <div>
                    <label class="arch-form-label"><?php echo RPT_ORIEN; ?></label>
                    <div style="display:flex; gap:24px; padding:10px 0;">
                        <?php if ($myrow['paperorientation']=='P') $selected = ' checked'; else  $selected = ''; ?>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:600;">
                            <input name="PaperOrientation" type="radio" value="P"<?php echo $selected ?> style="accent-color:var(--primary); width:18px; height:18px;">
                            <i class="fas fa-file"></i> <?php echo RPT_PORTRAIT; ?>
                        </label>
                        <?php if ($myrow['paperorientation']=='L') $selected = ' checked'; else  $selected = ''; ?>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:600;">
                            <input name="PaperOrientation" type="radio" value="L"<?php echo $selected ?> style="accent-color:var(--primary); width:18px; height:18px;">
                            <i class="fas fa-file" style="transform:rotate(90deg);"></i> <?php echo RPT_LANDSCAPE; ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Margins Card -->
        <div class="arch-card">
            <div class="arch-card-header">
                <h3 class="arch-card-title"><i class="fas fa-arrows-to-dot"></i> <?php echo RPT_PGMARGIN; ?></h3>
            </div>
            <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label class="arch-form-label"><?php echo RPT_TOP; ?> (mm)</label>
                    <input name="MarginTop" type="number" class="arch-form-input" value="<?php echo $myrow['margintop']; ?>">
                </div>
                <div>
                    <label class="arch-form-label"><?php echo RPT_BOTTOM; ?> (mm)</label>
                    <input name="MarginBottom" type="number" class="arch-form-input" value="<?php echo $myrow['marginbottom']; ?>">
                </div>
                <div>
                    <label class="arch-form-label"><?php echo RPT_LEFT; ?> (mm)</label>
                    <input name="MarginLeft" type="number" class="arch-form-input" value="<?php echo $myrow['marginleft']; ?>">
                </div>
                <div>
                    <label class="arch-form-label"><?php echo RPT_RIGHT; ?> (mm)</label>
                    <input name="MarginRight" type="number" class="arch-form-input" value="<?php echo $myrow['marginright']; ?>">
                </div>
            </div>
        </div>
    </div>

    <?php if ($Type<>'frm') { ?>
    <!-- Typography Mastery Card -->
    <div class="arch-card" style="margin-top:24px;">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-font"></i> <?php echo __('Typography & Header Elements'); ?></h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="smooth-table" style="font-size:0.8rem;">
                <thead>
                    <tr>
                        <th style="padding:15px; background:#f8fafc;"><?php echo __('Element'); ?></th>
                        <th style="padding:15px; background:#f8fafc; text-align:center;"><?php echo RPT_SHOW; ?></th>
                        <th style="padding:15px; background:#f8fafc;"><?php echo RPT_FONT; ?></th>
                        <th style="padding:15px; background:#f8fafc;"><?php echo RPT_SIZE; ?></th>
                        <th style="padding:15px; background:#f8fafc;"><?php echo RPT_COLOR; ?></th>
                        <th style="padding:15px; background:#f8fafc;"><?php echo RPT_ALIGN; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $TypographyElements = [
                        'CoyName' => ['label' => RPT_PGCOYNM, 'prefix' => 'CoyName', 'show_desc' => false],
                        'Title1'  => ['label' => RPT_PGTITL1, 'prefix' => 'Title1', 'show_desc' => true],
                        'Title2'  => ['label' => RPT_PGTITL2, 'prefix' => 'Title2', 'show_desc' => true],
                        'Filter'  => ['label' => RPT_PGFILDESC, 'prefix' => 'Filter', 'show_desc' => false],
                        'Data'    => ['label' => RPT_RPTDATA, 'prefix' => 'Data', 'show_desc' => false],
                        'Totals'  => ['label' => RPT_TOTALS, 'prefix' => 'Totals', 'show_desc' => false]
                    ];

                    foreach ($TypographyElements as $tag => $info) {
                        $p = $info['prefix'];
                        $show_key = strtolower($p).'show';
                        $font_key = strtolower($p).'font';
                        $size_key = strtolower($p).'fontsize';
                        $color_key = strtolower($p).'fontcolor';
                        $align_key = strtolower($p).'fontalign';
                        if ($tag == 'Filter' || $tag == 'Data' || $tag == 'Totals') $align_key = strtolower($p).'fontalign'; // fixed alignment key logic

                        echo '<tr>
                                <td style="padding:12px 20px; font-weight:700; color:#1e293b;">
                                    ' . $info['label'];
                                    if ($info['show_desc']) {
                                        echo '<div style="margin-top:6px;"><input name="'.$p.'Desc" type="text" value="'.$myrow[strtolower($p).'desc'].'" class="arch-form-input" style="height:32px; font-size:0.75rem;"></div>';
                                    }
                        echo '  </td>
                                <td align="center">
                                    ' . (isset($myrow[$show_key]) ? '<input name="'.$p.'Show" type="checkbox" value="1" style="width:18px; height:18px; accent-color:var(--primary);" '.($myrow[$show_key]=='1' ? 'checked' : '').'>' : '<i class="fas fa-check-circle" style="color:#cbd5e1;"></i>') . '
                                </td>
                                <td>
                                    <select name="'.$p.'Font" class="arch-form-input" style="height:32px; font-size:0.75rem;">';
                                    foreach($Fonts as $key => $value) {
                                        echo '<option value="'.$key.'" '.($myrow[$font_key]==$key ? 'selected':'').'>'.$value.'</option>';
                                    }
                        echo '      </select>
                                </td>
                                <td>
                                    <select name="'.$p.'FontSize" class="arch-form-input" style="height:32px; font-size:0.75rem;">';
                                    foreach($FontSizes as $key => $value) {
                                        echo '<option value="'.$key.'" '.($myrow[$size_key]==$key ? 'selected':'').'>'.$value.'</option>';
                                    }
                        echo '      </select>
                                </td>
                                <td>
                                    <select name="'.$p.'FontColor" class="arch-form-input" style="height:32px; font-size:0.75rem;">';
                                    foreach($FontColors as $key => $value) {
                                        echo '<option value="'.$key.'" '.($myrow[$color_key]==$key ? 'selected':'').'>'.$value.'</option>';
                                    }
                        echo '      </select>
                                </td>
                                <td>
                                    <select name="'.$p.'FontAlign" class="arch-form-input" style="height:32px; font-size:0.75rem;">';
                                    foreach($FontAlign as $key => $value) {
                                        echo '<option value="'.$key.'" '.($myrow[$align_key]==$key ? 'selected':'').'>'.$value.'</option>';
                                    }
                        echo '      </select>
                                </td>
                              </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Column Widths Explorer -->
    <div class="arch-card" style="margin-top:24px;">
        <div class="arch-card-header">
            <h3 class="arch-card-title"><i class="fas fa-arrows-left-right"></i> <?php echo RPT_CWDEF; ?></h3>
        </div>
        <div style="padding:20px;">
            <div style="display:grid; grid-template-columns:repeat(10, 1fr); gap:12px;">
                <?php for($i=1; $i<=20; $i++) { ?>
                    <div>
                        <label class="arch-form-label" style="font-size:0.6rem; text-align:center;"><?php echo __('Col').' '.$i; ?></label>
                        <input name="Col<?php echo $i; ?>Width" type="number" class="arch-form-input" 
                               value="<?php echo $myrow['col'.$i.'width']; ?>" style="padding:0; text-align:center; font-size:0.8rem; height:40px;">
                    </div>
                <?php } ?>
            </div>
            <p style="font-size:0.7rem; color:#64748b; margin-top:15px; font-weight:500;">
                <i class="fas fa-circle-info" style="color:var(--primary);"></i> <?php echo __('Widths are defined in percentage (%) of the total page width.'); ?>
            </p>
        </div>
    </div>
    <?php } // end if ($Type<>'frm') ?>
</form>
