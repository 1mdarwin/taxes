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
	}
  /**
   * Show a message during query taxes is disabled
   * @return array
   *    message into markup section
   */
  public function show_message(){
    $config = \Drupal::config('taxes.taxesconfig');
		$message = $config->get('endofyear_message');

		return array (
			'#markup' => $message,
		);
	}
}
