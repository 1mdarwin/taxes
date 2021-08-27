<?php

namespace Drupal\taxes\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the d9test module.
 */
class ConsultaController {

	public function showres(){
		# $mensaje = '<h2>Hola Mundo</h2>, esta es mi pagina';
		$response = $_SESSION['dataws'];
		$profile = $response['taxpayer'];
		$bonds = $response['bonds'];
		/*dsm($response);
		dsm($profile);	
		dsm($bonds);*/
		return [
			'#theme' => 'taxes_theme_hook',
			'#var1' => $profile,
			'#var2' => $bonds,
		];
		// return array (
		// 	'#markup' => $mensaje,
		// );
	}	
}