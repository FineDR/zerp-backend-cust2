<?php
ob_start();

$PageSecurity = 1;
require_once('includes/DefineCartClass.php');
require_once('includes/session.php');

// Polyfills for stability
if (!extension_loaded('curl')) {
    if (!defined('CURLOPT_URL')) define('CURLOPT_URL', 10002);
    if (!defined('CURLOPT_RETURNTRANSFER')) define('CURLOPT_RETURNTRANSFER', 19913);
}

// Silence errors for the binary output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('zlib.output_compression', 'Off');

require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
$conn = $db;

$invoice_no = $_GET['InvoiceNo'] ?? $_GET['BatchNumber'] ?? '0';

if ($invoice_no == '0') {
    if (ob_get_length()) ob_end_clean();
    die("No Invoice Number provided.");
}

// Industry Standard Page Size (58mm width, 250mm length for thermal flow)
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', array(58, 250), true, 'UTF-8', false);
$pdf->SetMargins(2, 5, 2);
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Professional Thermal Typography
$pdf->SetFont('courier', '', 8);
$pdf->setCellHeightRatio(1.2);

// 1. Company Details Retrieval
$sql_comp = mysqli_query($conn, "SELECT coyname, regoffice1, regoffice2, telephone, email FROM companies LIMIT 1");
$row_comp = mysqli_fetch_assoc($sql_comp);
$comp_name = $row_comp['coyname'] ?? $_SESSION['CompanyRecord']['coyname'] ?? 'Company Name';
$caddress = ($row_comp['regoffice1'] ?? '') . ' ' . ($row_comp['regoffice2'] ?? '');
$cmobile = $row_comp['telephone'] ?? '';

// 2. Fiscal Registration data
$cvrn = 'UNREGISTERED'; $ctin = ''; $cefdserial = ''; $cuser = '';
$res_efd_check = mysqli_query($conn, "SHOW TABLES LIKE 'efd_registration_data'");
if ($res_efd_check && mysqli_num_rows($res_efd_check) > 0) {
    $res_efd = mysqli_query($conn, "SELECT * FROM efd_registration_data ORDER BY created_at DESC LIMIT 1");
    if ($res_efd && mysqli_num_rows($res_efd) > 0) {
        $row_efd = mysqli_fetch_assoc($res_efd);
        $cvrn = $row_efd['vrn'] ?? 'UNREGISTERED';
        $ctin = $row_efd['tin'] ?? '';
        $cefdserial = $row_efd['serial'] ?? '';
        $cuser = $row_efd['vin'] ?? '';
    }
}

// 3. Transaction Details
$sql_trans = "SELECT debtortrans.trandate, debtortrans.debtorno, debtortrans.type, debtortrans.ovamount, debtortrans.ovgst, debtorsmaster.name, currencies.currabrev AS currcode
              FROM debtortrans 
              INNER JOIN debtorsmaster ON debtortrans.debtorno = debtorsmaster.debtorno
              INNER JOIN currencies ON debtorsmaster.currcode = currencies.currabrev
              WHERE debtortrans.transno='" . $invoice_no . "' AND (debtortrans.type=10 OR debtortrans.type=12 OR debtortrans.type=11)";
$res_trans = mysqli_query($conn, $sql_trans);
$row_trans = mysqli_fetch_assoc($res_trans);
$cust_name = $row_trans['name'] ?? 'CASH CUSTOMER';
$currcode = $row_trans['currcode'] ?? 'TZS';
$rct_date = isset($row_trans['trandate']) ? date('d/m/Y', strtotime($row_trans['trandate'])) : date('d/m/Y');
$trans_type = $row_trans['type'] ?? 10;

// 4. Fiscal Ack data (Verification & QR)
$rct_rctnum = 'NOT FISCALIZED'; $z_number = ''; $verificationcode = ''; $filename = '';
$res_ack_exist = mysqli_query($conn, "SHOW TABLES LIKE 'ReceiptAck'");
if ($res_ack_exist && mysqli_num_rows($res_ack_exist) > 0) {
    $res_ack = mysqli_query($conn, "SELECT * FROM ReceiptAck WHERE invoiceNumber = '$invoice_no'");
    if ($res_ack && mysqli_num_rows($res_ack) > 0) {
        $row_ack = mysqli_fetch_assoc($res_ack);
        $rct_rctnum = $row_ack['receiptNumber'] ?? '000000';
        $z_number = $row_ack['znumber'] ?? '00000';
        $verificationcode = $row_ack['verificationCode'] ?? '';
        $filename = $row_ack['qrCodePath'] ?? '';
    }
}

// 5. Items Preparation
$items_html = ''; $total_net = 0; $total_vat = 0;
$sql_items = "SELECT stockid, stockid AS itemdescription, -qty AS quantity, price AS unitprice, discountpercent, 18 AS taxrate
              FROM stockmoves WHERE type = $trans_type AND transno = '".$invoice_no."'";
$result_items = mysqli_query($conn, $sql_items);

if ($result_items && mysqli_num_rows($result_items) > 0) {
    while($item = mysqli_fetch_assoc($result_items)){
        $net = $item['quantity'] * $item['unitprice'] * (1 - $item['discountpercent']);
        $vat = $net * (($item['taxrate'] ?? 18) / 100);
        $total_net += $net; $total_vat += $vat;
        $items_html .= '
        <tr><td colspan="2" style="padding-top:4px;">'.htmlspecialchars($item['itemdescription']).'</td></tr>
        <tr>
            <td style="padding-bottom:2px;">'.number_format($item['quantity'], 2).' x '.number_format($item['unitprice'], 2).'</td>
            <td align="right" style="padding-bottom:2px;">'.number_format($net + $vat, 2).'</td>
        </tr>';
    }
} else {
    $total_gross = abs($row_trans['ovamount'] + $row_trans['ovgst']);
    $total_vat = abs($row_trans['ovgst']);
    $total_net = $total_gross - $total_vat;
    $desc = ($trans_type == 12) ? 'Payment Receipt' : 'Service Transaction';
    $items_html = '
    <tr><td colspan="2" style="padding-top:4px;">'.$desc.'</td></tr>
    <tr>
        <td style="padding-bottom:2px;">1.00 x '.number_format($total_gross, 2).'</td>
        <td align="right" style="padding-bottom:2px;">'.number_format($total_gross, 2).'</td>
    </tr>';
}

// 6. Modern Fiscal Receipt HTML Construction
$html = '
<style>
    body { font-family: courier; font-size: 8px; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; }
    .hr { border-top: 1px dashed #000; height: 1px; line-height: 1px; margin: 4px 0; }
    .company-name { font-size: 11px; font-weight: bold; }
    .receipt-title { font-size: 10px; font-weight: bold; }
    .total-line { font-size: 9px; font-weight: bold; }
    .logo-space { padding: 5px 0; }
    .qr-space { padding: 8px 0; }
    .legal-heading { font-weight: bold; margin-bottom: 5px; }
</style>

<table border="0" cellpadding="0" cellspacing="0">
    <!-- 1. Legal Header -->
    <tr><td align="center" class="legal-heading">*** START OF LEGAL RECEIPT ***</td></tr>
    
    <!-- 2. TRA Logo Placement -->
    <tr><td align="center" class="logo-space"><img src="' . __DIR__ . '/css/TRAlogo.png" width="45" /></td></tr>
    
    <!-- 3. Company Section -->
    <tr><td align="center" class="company-name">'.htmlspecialchars($comp_name).'</td></tr>
    <tr><td align="center">'.htmlspecialchars($caddress).'</td></tr>
    <tr><td align="center">TEL: '.htmlspecialchars($cmobile).'</td></tr>
    <tr><td align="center">TIN: '.htmlspecialchars($ctin).' | VRN: '.htmlspecialchars($cvrn).'</td></tr>
    <tr><td align="center">SERIAL: '.htmlspecialchars($cefdserial).'</td></tr>
    <tr><td align="center">UIN: '.htmlspecialchars($cuser).'</td></tr>
    
    <tr><td class="hr"></td></tr>
    
    <!-- 4. Receipt Title -->
    <tr><td align="center" class="receipt-title">TAX RECEIPT</td></tr>
    
    <tr><td class="hr"></td></tr>
    
    <!-- 5. Receipt Information -->
    <tr><td>
        <table>
            <tr><td width="40%">Receipt No:</td><td align="right">'.htmlspecialchars($rct_rctnum).'</td></tr>
            <tr><td width="40%">Z Number  :</td><td align="right">'.htmlspecialchars($z_number).'</td></tr>
            <tr><td width="40%">Date      :</td><td align="right">'.htmlspecialchars($rct_date).' '.date('H:i').'</td></tr>
            <tr><td width="40%">Customer  :</td><td align="right">'.htmlspecialchars($cust_name).'</td></tr>
        </table>
    </td></tr>
    
    <tr><td class="hr"></td></tr>
    
    <!-- 6. Items Section -->
    <tr><td>
        <table border="0" cellpadding="0">
            '.$items_html.'
        </table>
    </td></tr>
    
    <tr><td class="hr"></td></tr>
    
    <!-- 7. Totals Section -->
    <tr><td>
        <table>
            <tr><td>Total Excl Tax</td><td align="right">'.number_format($total_net, 2).'</td></tr>
            <tr><td>VAT (18%)</td><td align="right">'.number_format($total_vat, 2).'</td></tr>
            <tr><td class="total-line">TOTAL ('.htmlspecialchars($currcode).')</td><td align="right" class="total-line">'.number_format($total_net + $total_vat, 2).'</td></tr>
        </table>
    </td></tr>
    
    <tr><td class="hr"></td></tr>
    
    <!-- 8. QR & Verification Section (At the Bottom) -->';

if (!empty($verificationcode)) {
    $html .= '<tr><td align="center" class="qr-space">
        Verification Code:<br/><b>'.htmlspecialchars($verificationcode).'</b><br/><br/>';
    
    // Resolve absolute path for QR Code image
    $qr_path = $filename;
    if (!empty($qr_path) && !file_exists($qr_path)) {
        $qr_path = __DIR__ . '/' . $qr_path;
    }

    if (!empty($qr_path) && file_exists($qr_path)) {
        $html .= '<img src="'.htmlspecialchars($qr_path).'" width="60" /><br/>';
    }
    
    $html .= '</td></tr>';
} elseif (!empty($filename)) {
    // Fallback if only QR path exists
    $qr_path = $filename;
    if (!file_exists($qr_path)) {
        $qr_path = __DIR__ . '/' . $qr_path;
    }
    if (file_exists($qr_path)) {
        $html .= '<tr><td align="center" class="qr-space"><img src="'.htmlspecialchars($qr_path).'" width="60" /></td></tr>';
    }
}

$html .= '
    <!-- 9. Legal Footer -->
    <tr><td align="center" class="legal-heading">*** END OF LEGAL RECEIPT ***</td></tr>
    <tr><td align="center" style="font-size: 7px; padding-top: 5px;">Thank you for your business</td></tr>
</table>';

$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');

// Clean output buffer before sending headers
if (ob_get_length()) ob_end_clean();

$output_type = (isset($_GET['Download']) && $_GET['Download'] == 'True') ? 'D' : 'I';
$filename_out = 'Receipt-' . $invoice_no . '.pdf';

$pdf->Output($filename_out, $output_type);
exit;