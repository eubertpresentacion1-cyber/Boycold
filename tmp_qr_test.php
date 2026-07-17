<?php
require 'vendor/autoload.php';

$qrCode = new Endroid\QrCode\QrCode('CARD-12345', new Endroid\QrCode\Encoding\Encoding('UTF-8'), Endroid\QrCode\ErrorCorrectionLevel::Low, 220, 6);
$writer = new Endroid\QrCode\Writer\SvgWriter();
$result = $writer->write($qrCode);
echo 'svg-qr-ok:' . strlen($result->getString()) . PHP_EOL;
