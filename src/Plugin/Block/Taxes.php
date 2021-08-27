<?php
# https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/dependency-injection-for-a-form

namespace Drupal\taxes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block of cats ... you can't make this stuff
 *
 * @Block (
 *   id = "taxes_block",
 *   admin_label = @Translation("Taxes")
 * )
 */

class Taxes extends BlockBase implements ContainerFactoryPluginInterface {
	/**
	 * @var \Drupal\taxes\TaxesClient
	 */	
	protected $catFactsClient;
	
	/**
	 * CatFacts constructor
	 *
	 * @param  array $configuration
	 * @param  $plugin_id
	 * @param  $plugin_definition
	 * @param  $catfacts_client \Drupal\taxes\TaxesClient
	 */
	function __construct(array $configuration, $plugin_id, $plugin_definition, $catfacts_client){
		parent::__construct($configuration, $plugin_id, $plugin_definition);
		$this->catFactsClient = $catfacts_client;
	}

	/**
	 * {@inheritdoc}
	 */	
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition){
		return new static(
			$configuration,
			$plugin_id,
			$plugin_definition,
			// $container->get('taxes_client')
			$container->get('impuestos_client')
		);

	}
	/**
	 * {@inheritdoc}
	 */	
	public function build(){		
		// $cats_facts = $this->catFactsClient->random(2);
		$cats_facts = $this->catFactsClient->withCedula('1104262835');				
		$items = [];

		foreach ($cats_facts as $key => $cat_fact) {
			// kint($key);
			if ($key === 'taxpayer'){
				// kint ($cat_fact['id']);
				// $items[] = $cat_fact;
				foreach ($cat_fact as $k => $values){
					$items[] = $k . ': '. $values;
				}
			}
			# code...
			// $items[] = $cat_fact['text'];
			// $items[] = $cat_fact['taxpayer'];
		}

		return [
			'#theme' => 'item_list',
			'#items' => $items
		];
	}


}