<?php
namespace APP;

require(__DIR__.'/akou/src/RestController.php');
require_once __DIR__ . '/vendor/autoload.php';

use \akou\RestController;
use \akou\Utils;
use \akou\ArrayUtils;
use \akou\ValidationException;
use \akou\LoggableException;

class Service extends RestController
{
	function options()
	{
		$this->setAllowHeader();
		return $this->defaultOptions();
	}
	function get()
	{
		$params = $this->getMethodParams();
		$orientation = $params['orientation']??'P';
		$default_font_size = $params['default_font_size']??9;
		$default_font = $params['default_font']??'helvetica';
		$download_name = $params['download_name']?? '';

		return $this->getPdf( $this->getHtml(), $orientation, $default_font_size, $default_font, $download_name );
	}

	function post()
	{
		$params = $this->getMethodParams();
		$orientation = $params['orientation']??'P';
		$default_font_size = $params['default_font_size']??9;
		$default_font = $params['default_font']??'helvetica';
		$download_name = $params['download_name']? $params['download_name'] : '';

		return $this->getPdf( $this->getHtml(), $orientation, $default_font_size, $default_font, $download_name );
	}

	function getPdf($html, $orientation='P',$default_font_size=9,$default_font='helvetica', $download_name='')
	{
		error_log("Printing PDF");
		$mpdf = new \Mpdf\Mpdf
		([
			"tempDir"=> "/tmp",
			'default_font'=>$default_font,
			'default_font_size'=>$default_font_size,
			'orientation'=> $orientation
		]);

		try
		{
			$mpdf->WriteHTML( $html );
			$string_attach = $mpdf->Output('output.pdf','S');

			header('Content-Type: application/pdf');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
			header('Cache-Control: no-cache, must-revalidate');

			if($download_name)
			{
				$sanitized_filename = preg_replace('/[^a-zA-Z0-9_.-]/', '', $download_name);
				error_log("Sanitized filename $sanitized_filename");
				header('Content-Disposition: attachment; filename="' .$sanitized_filename. '"');
			}

			return $this->sendStatus(200)->raw( $string_attach );
		}
		catch (\Exception $e)
		{
			return $this->sendStatus(500)->text( "Error al generar el PDF. {$e->getMessage()}" );
		}

		return $this->sendStatus(500)->text( "Error al generar el PDF." );
	}

	function getHtml()
	{
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
			<head>
				<title>Test Print Header</title>
			</head>
			<body>
				<!--img src="https://trikitrakes.integranet.xyz/api/image.php?id=1&width=300"-->
				<h1>This is a test header</h1>
				<table style="width:100%">
					<thead>
						<tr>
							<th>Column 1</th>
							<th>Column 2</th>
						</tr>
					</thead>
					<tbody>
						<?php for($i=0;$i<100;$i++): ?>
							<tr>
								<td>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</td>
								<td>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</td>
							</tr>
							<tr>
								<td>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</td>
								<td>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</td>
							</tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</body>
		</html>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}

$s = new Service();
$s->execute();
