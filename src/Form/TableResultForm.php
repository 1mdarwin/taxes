<?php

namespace Drupal\taxes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\Order;

/**
 * Class TableForm.
 */
class TableResultForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tableresult_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $sum = 0;
    //  dsm($_POST);

    // dpm($_SESSION['taxes']);
    $ced = $_SESSION['cedula']; // Get cedula from session var
    $cliente = $_SESSION['contribuyente']; // Get client's name from session var

    $header = [
      'codSer' => $this->t('Codigo de Servicio'),
      'rubro'   => $this->t('Rubro'),
      'fInicio' => $this->t('Fecha de Servicio'),
      'fFin'    => $this->t('Fecha de Expiración'),
      'monto'   => $this->t('Valor'),
    ];

    if(empty($_SESSION['dataws'])){
      $_SESSION['dataws'] = NULL;
    }else{
      $dataws = $_SESSION['dataws'];  // Recover data from session
    }
    if(sizeof($dataws) > 0){
      foreach ($dataws as $tax) {
        $options[$tax['sku']] = array(
          'codSer'  => $tax['codSer'],
          'rubro'   => $tax['rubro'],
          'fInicio' => $tax['fInicio'],
          'fFin'    => $tax['fFin'],
          'monto'   => $tax['monto'],
        );

        $sum += (float) $tax['monto'];
        $sum = number_format((float)$sum, 2, '.', ''); // Format with 2 decimals
      }
      asort($dataws); // Sort by codServ and Date but the key is changed
    }


    $options = $this->reindexKey($dataws, false);

    $form['impuestos'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Impuestos'),
      '#header' => $header,
      '#options' => $options,
      '#description' => $this->t('impuestos a presentar'),
      '#weight' => '0',
      '#prefix'   => '<div class="contribuyente">
			      <div class="nombrecon"><strong>Nombre: </strong>' .$cliente. '</div><div class="apecon"><strong>Cédula: </strong>' .$ced. '</div><div><strong>Rubro Seleccionado:</strong> '.$_SESSION['rubro'].'</div>
			    </div>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#prefix' => ' <div class="totalq">' .$sum. '</div>',
	    '#suffix' => '<div class="nuevacon"><a class="nueva" href="'.$base_url.'/taxes/consulta">Nueva Consulta</a></div>
			  <div class="notas">
			    <p><strong>RECUERDE:</strong>Se pueden efectuar pagos con tarjeta de crédito en DIFERIDO desde 10 USD en adelante</p>
			    <p><strong></strong>Pagos con tarjeta débito y tarjeta de crédito en CORRIENTE HASTA 300 USD</p>
			    <div><strong>Tarjetas Aceptadas:</strong>
				<ul>
				  <li>VISA/MASTERCARD BANCO PICHINCHA</li>
				  <li>VISA BANCO GENERAL RUMIÑAHUI</li>
				  <li>VISA BANCO DE LOJA</li>
				  <li>DINERS</li>
				  <li>DISCOVER</li>
				</ul>
			     </div>
			    <div class="manualpagos"><a href="https://goo.gl/vqw9x3">Descargar manual de pagos</a></div>
			  </div>',
	    '#value'  =>  $this->t('Pagar Impuestos'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
    $order = $this->createOrderCustom(); // Get the order created

    // $form_state->setRedirect('commerce_checkout.form', ['commerce_order' => $order->id()]); // Redirect
    $form_state->setRedirect('commerce_cart.page', ['commerce_order' => $order->id()]); // Redirect

  }
  /**
   * Allow to create an order from code with custom fields using order type item
   */
  public function createOrderCustom(){
    $order_item = OrderItem::create([
      // 'type' => 'default',  // Order Item Type
      'type' => 'impuestolineorder',  // Order Item Type
      'purchased_entity' => '4', // Must be string always
      'quantity' => 1,
      // Omit these lines to preserve original product price.
      'unit_price' => new Price(80, 'USD'),
      'overridden_unit_price' => TRUE,
    ]);
    $order_item->save();

    $entity_manager = \Drupal::entityTypeManager();
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $store = $entity_manager->getStorage('commerce_store')->load(2);
    $cart = $cart_provider->getCart('default', $store);
    if (!$cart) {
      $cart = $cart_provider->createCart('default', $store);
    }
    $cart_manager->addOrderItem($cart, $order_item);

    // Next we create the billing profile.
    $profile = \Drupal\profile\Entity\Profile::create([
      'type' => 'customer',
      'uid' => 1, // The user id that the billing profile belongs to.
    ]);
    $profile->save();

    $order = Order::create([
      'type' => 'default',
      'mail' => \Drupal::currentUser()->getEmail(),
      'uid' => \Drupal::currentUser()->id(),
      'store_id' => 2,
      'order_items' => [$order_item],
      'placed' => \Drupal::time()->getCurrentTime(),
      'payment_gateway' => 'example_payment',
      'checkout_step' => 'payment',
      'billing_profile' => $profile, // The profile we just created.
      'state' => 'draft',
    ]);

    $order->recalculateTotalPrice();
    $order->save();

    $order->set('order_number', $order->id());
    $order->save();

    return $order;
  }
  /**
 * Function for reorder array start with key from 1
 * @param $data
 *    (Array) An array order with asort function
 * @param $all
 *    (Boolean) To know if there is all elements include Id field Bonds
 * @return $allData
 *    Multidimentional Array with many values
 */

  public function reindexKey($data, $all){
    $of1 = array();
    $i =0;
    foreach ($data as $tax) {
      $i +=1;
      if($all){ // for submit include more data for register
        $allData[$i] = array(
            'codSer'  => $tax['codSer'],
            'rubro'   => $tax['rubro'],
            'fInicio' => $tax['fInicio'],
            'fFin'    => $tax['fFin'],
            'monto'   => $tax['monto'],
            'id'  => $tax['id'],
            'number'  => $tax['number'],
            'sku'  => $tax['sku'],
        );
      }else{ // for validate before submit
        $allData[$i] = array(
            'codSer'  => $tax['codSer'],
            'rubro'   => $tax['rubro'],
            'fInicio' => $tax['fInicio'],
            'fFin'    => $tax['fFin'],
            'monto'   => $tax['monto'],
        );
      }
    }
    return $allData;
  }


}
