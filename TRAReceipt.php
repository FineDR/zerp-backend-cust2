<?php
ob_start();

$PageSecurity = 1;
require_once('includes/DefineCartClass.php');
require_once('includes/session.php');

// Polyfills
if (!extension_loaded('curl')) {
    if (!defined('CURLOPT_URL')) define('CURLOPT_URL', 10002);
    if (!defined('CURLOPT_RETURNTRANSFER')) define('CURLOPT_RETURNTRANSFER', 19913);
    if (!defined('CURLOPT_CONNECTTIMEOUT')) define('CURLOPT_CONNECTTIMEOUT', 78);
    if (!defined('CURLOPT_TIMEOUT')) define('CURLOPT_TIMEOUT', 13);
    if (!defined('CURLOPT_MAXREDIRS')) define('CURLOPT_MAXREDIRS', 68);
    if (!defined('CURLOPT_HEADER')) define('CURLOPT_HEADER', 42);
    if (!defined('CURLOPT_USERAGENT')) define('CURLOPT_USERAGENT', 10018);
    if (!defined('CURLOPT_FOLLOWLOCATION')) define('CURLOPT_FOLLOWLOCATION', 52);
    if (!defined('CURLOPT_SSL_VERIFYPEER')) define('CURLOPT_SSL_VERIFYPEER', 64);
    if (!defined('CURLOPT_SSL_VERIFYHOST')) define('CURLOPT_SSL_VERIFYHOST', 81);
    if (!defined('CURLOPT_FAILONERROR')) define('CURLOPT_FAILONERROR', 45);
    if (!defined('CURLOPT_PROTOCOLS')) define('CURLOPT_PROTOCOLS', 181);
    if (!defined('CURLPROTO_HTTP')) define('CURLPROTO_HTTP', 1);
    if (!defined('CURLPROTO_HTTPS')) define('CURLPROTO_HTTPS', 2);
    if (!defined('CURLPROTO_FTP')) define('CURLPROTO_FTP', 4);
    if (!defined('CURLPROTO_FTPS')) define('CURLPROTO_FTPS', 8);
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

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', array(58, 210), true, 'UTF-8', false);
$pdf->SetMargins(2, 5, 2);
$pdf->SetAutoPageBreak(TRUE, 5);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$font = 'courier';
$pdf->SetFont($font, '', 8.5);

// 1. Company Details
$sql_comp = mysqli_query($conn, "SELECT coyname, regoffice1, regoffice2, telephone, email FROM companies LIMIT 1");
$row_comp = mysqli_fetch_assoc($sql_comp);
$comp_name = $row_comp['coyname'] ?? $_SESSION['CompanyRecord']['coyname'] ?? 'Company Name';
$caddress = ($row_comp['regoffice1'] ?? '') . ' ' . ($row_comp['regoffice2'] ?? '');
$cmobile = $row_comp['telephone'] ?? '';

// 2. Registration data (Safe query)
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

// 4. Fiscal Ack data
$rct_rctnum = 'NOT FISCALIZED'; $z_number = ''; $verificationcode = ''; $filename = '';
$res_ack_exist = mysqli_query($conn, "SHOW TABLES LIKE 'ReceiptAck'");
if ($res_ack_exist && mysqli_num_rows($res_ack_exist) > 0) {
    $res_ack = mysqli_query($conn, "SELECT * FROM ReceiptAck WHERE invoiceNumber = '$invoice_no'");
    if ($res_ack && mysqli_num_rows($res_ack) > 0) {
        $row_ack = mysqli_fetch_assoc($res_ack);
        $rct_rctnum = $row_ack['receiptNumber'];
        $z_number = $row_ack['znumber'];
        $verificationcode = $row_ack['verificationCode'];
        $filename = $row_ack['qrCodePath'];
    }
}

// 5. Items
$items_html = ''; $total_net = 0; $total_vat = 0;
$sql_items = "SELECT stockid, stockid AS itemdescription, -qty AS quantity, price AS unitprice, discountpercent, 18 AS taxrate
              FROM stockmoves WHERE type = $trans_type AND transno = '".$invoice_no."'";
$result_items = mysqli_query($conn, $sql_items);

if ($result_items && mysqli_num_rows($result_items) > 0) {
    while($item = mysqli_fetch_assoc($result_items)){
        $net = $item['quantity'] * $item['unitprice'] * (1 - $item['discountpercent']);
        $vat = $net * (($item['taxrate'] ?? 18) / 100);
        $total_net += $net; $total_vat += $vat;
        $items_html .= '<tr><td colspan="3">'.$item['itemdescription'].'</td></tr>
        <tr><td>'.number_format($item['quantity'], 2).' x '.number_format($item['unitprice'], 2).'</td><td align="right">'.number_format($net + $vat, 2).'</td><td align="right">A</td></tr>';
    }
} else {
    // For receipts (type 12) or GL invoices without stockmoves
    $total_gross = abs($row_trans['ovamount'] + $row_trans['ovgst']);
    $total_vat = abs($row_trans['ovgst']);
    $total_net = $total_gross - $total_vat;
    
    $desc = ($trans_type == 12) ? 'Payment Receipt' : 'Service/GL Transaction';
    $items_html = '<tr><td colspan="3">'.$desc.'</td></tr>
    <tr><td>1.00 x '.number_format($total_gross, 2).'</td><td align="right">'.number_format($total_gross, 2).'</td><td align="right">A</td></tr>';
}

$html = '
<table border="0" cellspacing="0" cellpadding="1" style="width:100%">
    <tr><td colspan="3" align="center">*** START OF LEGAL RECEIPT ***</td></tr>
    <tr><td colspan="3" align="center"><img src="' . __DIR__ . '/css/TRAlogo.png" width="50" /></td></tr>
    <tr><td colspan="3" align="center"><b>'.htmlspecialchars($comp_name).'</b></td></tr>
    <tr><td colspan="3" align="center">'.htmlspecialchars($caddress).'</td></tr>
    <tr><td colspan="3" align="center">TEL: '.htmlspecialchars($cmobile).' | TIN: '.htmlspecialchars($ctin).'</td></tr>
    <tr><td colspan="3" align="center">SERIAL: '.htmlspecialchars($cefdserial).' | UIN: '.htmlspecialchars($cuser).'</td></tr>
    <tr><td colspan="3">..................................................................</td></tr>
    <tr><td colspan="3"><b>CUSTOMER:</b> '.htmlspecialchars($cust_name).'</td></tr>
    <tr><td colspan="3">..................................................................</td></tr>
    <tr><td colspan="2"><b>RECEIPT NO:</b></td><td align="right">'.htmlspecialchars($rct_rctnum).'</td></tr>
    <tr><td colspan="2"><b>Z NUMBER:</b></td><td align="right">'.htmlspecialchars($z_number).'</td></tr>
    <tr><td colspan="2"><b>DATE:</b></td><td align="right">'.htmlspecialchars($rct_date).' '.date('H:i:s').'</td></tr>
    <tr><td colspan="3">..................................................................</td></tr>
    <tr><td><b>Description</b></td><td align="right"><b>Total</b></td><td align="right"><b>Tax</b></td></tr>
    '.$items_html.'
    <tr><td colspan="3">..................................................................</td></tr>
    <tr><td>TOTAL EXCL TAX:</td><td colspan="2" align="right">'.number_format($total_net, 2).'</td></tr>
    <tr><td>TOTAL VAT:</td><td colspan="2" align="right">'.number_format($total_vat, 2).'</td></tr>
    <tr><td><b>TOTAL INCL TAX:</b></td><td colspan="2" align="right"><b>'.number_format($total_net + $total_vat, 2).' '.htmlspecialchars($currcode).'</b></td></tr>
    <tr><td colspan="3">..................................................................</td></tr>';

if (!empty($verificationcode)) $html .= '<tr><td colspan="3" align="center"><b>VERIFICATION CODE:</b><br/>'.htmlspecialchars($verificationcode).'</td></tr>';
if (!empty($filename) && file_exists($filename)) $html .= '<tr><td colspan="3" align="center"><img src="'.htmlspecialchars($filename).'" width="70" height="70" /></td></tr>';
$html .= '<tr><td colspan="3" align="center">*** END OF LEGAL RECEIPT ***</td></tr>
</table>';

$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');

// Clean output buffer before sending headers
if (ob_get_length()) ob_end_clean();

$output_type = (isset($_GET['Download']) && $_GET['Download'] == 'True') ? 'D' : 'I';
$filename_out = 'Receipt-' . $invoice_no . '.pdf';

header_remove('Content-Type');
header_remove('Pragma');
header_remove('Cache-Control');
header_remove('Content-Disposition');

header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($output_type == 'D' ? 'attachment' : 'inline') . '; filename="' . $filename_out . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('Cache-Control: private, must-revalidate, max-age=0');
header('Pragma: private');

$pdf->Output($filename_out, $output_type);