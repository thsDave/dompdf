<?php
require_once "main.php";

require 'vendor/autoload.php';

// require 'dompdf/autoload.inc.php';

// require 'dompdf/src/Dompdf.php';

use Dompdf\Dompdf;

$arr_docs = [
	'user_list' => 'Reporte de usuarios al '.date("dmY-His"),
	'support_list' => 'Reporte de soportes al '.date("dmY-His"),
	'custom_user_list' => 'Reporte de usuarios al '.date("dmY-His"),
	'custom_support_list' => 'Reporte de soportes al '.date("dmY-His"),
	'order_profile' => 'Informe de mantenimiento',
	'bin_profile' => 'Informe de bitácora'
];

if (isset($_GET['pdf_req']) && isset($arr_docs[$_GET['pdf_req']])) { $view = $_GET['pdf_req']; }

if (isset($view))
{
	ob_start();

	include $view.'.php';

	$dompdf = new Dompdf();
	$dompdf->loadHtml($aData['html']);
	$dompdf->set_option('isRemoteEnabled', TRUE);

	if (isset($_GET['horizontal']))
		$dompdf->set_paper("A4", "landscape");

	$dompdf->load_html(ob_get_clean());
	$dompdf->render();
	$pdf = $dompdf->output();
	$dompdf->stream($arr_docs[$view]);
}
else
{
	header("Location: ".URL);
}