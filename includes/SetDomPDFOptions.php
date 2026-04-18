<?php

/* set all options for DomPDF in one place for reusability and consistency */

$ProjectRoot = dirname(__DIR__);
$Autoload = $ProjectRoot . '/vendor/autoload.php';

if (!class_exists('Dompdf\\Options') and file_exists($Autoload)) {
	require_once($Autoload);
}

if (!class_exists('Dompdf\\Options')) {
	$Message = 'Dompdf is not available. Run composer install and check that vendor/autoload.php can be loaded.';
	if (function_exists('prnMsg')) {
		prnMsg($Message, 'error');
	} else {
		echo $Message;
	}
	exit();
}

$DomPDFOptions = new \Dompdf\Options();

$DomPDFOptions->set('isHtml5ParserEnabled', true);
$DomPDFOptions->set('isRemoteEnabled', true);

if (isset($SymlinkImageDir) and ($SymlinkImageDir != '')) {
	$DomPDFOptions->setChroot([$PathPrefix ?? $ProjectRoot, $SymlinkImageDir]);
} else {
	$DomPDFOptions->setChroot([$PathPrefix ?? $ProjectRoot]);
}
