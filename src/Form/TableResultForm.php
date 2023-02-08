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
    dpm($_POST);

    // dpm($_SESSION['taxes']);
    $tempstore = \Drupal::service('tempstore.private')->get('taxes_multipleform');
    $dataws = $tempstore->get('dataws');
    $ced = $_SESSION['cedula']; // Get cedula from session var
    $cliente = $_SESSION['contribuyente']; // Get client's name from session var

    $header = [
      'codSer' => $this->t('Codigo de Servicio'),
      'rubro'   => $this->t('Rubro'),
      'fInicio' => $this->t('Fecha de Servicio'),
      'fFin'    => $this->t('Fecha de Expiración'),
      'monto'   => $this->t('Valor'),
    ];

    // if(empty($_SESSION['dataws'])){
    //   $_SESSION['dataws'] = NULL;
    // }else{
    //   $dataws = $_SESSION['dataws'];  // Recover data from session
    // }
    // $dataws = $_SESSION['dataws'];  // Recover data from session
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

    $form['taxes'] = [
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
    global $base_url;
    // foreach ($form_state->getValues() as $field_name => $value) {
      // @TODO: Validate fields.
      //  $ced = $form_state['values']['cedula'];
        #global $ced;
        global $user;
        // $ced = $_SESSION['ced'];
      //  global $dataws;  // Have data with all taxes get from GIM system
        $dataws = $_SESSION['dataws'];
        $fechahora = date('H:i:s');

        asort($dataws);  // Sort taxes by codServ and Date like as tableselect above
        $dataws = $this->reindexKey((array)$dataws, TRUE);  // re Sort keys for array from key 1

        $lenA = sizeof((array) $dataws); //Get the array length
        $sel = array_filter($form_state->getValue('taxes')); // Get the values selected from tableselect
        $pagar = FALSE;

        $lenB = sizeof($sel);
        if($lenB != 0){
          foreach ($sel as $key) {
        if($key == 1){
          $pagar = TRUE;
        }else{
          $ant = $dataws[$key-1]['codSer'] . $dataws[$key-1]['rubro']; //concat string with codSer and rubro items
          $act = $dataws[$key]['codSer'] . $dataws[$key]['rubro'];

          if($ant == $act){
            if(in_array($key, $sel) && in_array($key-1, $sel)){
              $pagar = TRUE;
            }else{
              $pagar = FALSE;
              break;
            }
          }else{
            if(in_array($key-1, $sel)){
              if($ant == $act){
          $pagar = TRUE;
              }
            }else{
              if($ant == $act){
          $pagar = FALSE;
          break;
              }else{
          $pagar = TRUE;
              }
            }
          }
        }
          }// End foreach

          // The payment only can do it between 08 and 17 hours with thirty minutes
          //if (!($fechahora > "08:00:00" && $fechahora < "17:30:00")){
          if (!($fechahora > "07:30:00" && $fechahora < "23:55:00")){
            $form_state->setErrorByName('taxes', $this->t('The phone number is too short. Please enter a full phone number.'));
        }
        if(\Drupal::currentUser()->isAnonymous()){ //Check if the user is anonymous and ask register
          $form_state->setErrorByName('taxes', $this->t("Debe <a class='registro' href='". $base_url."/user/register'>registrarse</a> e ingresar al sitio para poder efectuar pagos. Si ya dispone de una cuenta <a class='registro' href='". $base_url."/user'>ingrese al sitio</a>"));
          // form_set_error("taxes","Debe <a class='registro' href='https://www.loja.gob.ec/user/register'>registrarse</a> e ingresar al sitio para poder efectuar pagos. Si ya dispone de una cuenta <a class='registro' href='https://www.loja.gob.ec/user'>ingrese al sitio</a>");
          // drupal_goto('https://www.loja.gob.ec/user');
        }

        /*if($lenA == $lenB){$pagar = TRUE;}
        else $pagar = FALSE;*/
        if($pagar){
          //drupal_set_message("Puede pagar");
          $dataws = array_intersect_key((array)$dataws, $sel); // Get intersection between keys to determine the selected items from complete array
          $_SESSION['dataws'] = $dataws;

        }else{
          $form_state->setErrorByName('taxes', $this->t('NO puede pagar los impuestos seleccionados debiendo meses anteriores'));

          //form_set_error("taxes","Debe pagar todos los impuestos listados");
        }
      }else{
        $form_state->setErrorByName('taxes', $this->t('Debe elegir rubros para realizar el pago'));
      }// End if lenB !=0
    // }
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
   * Create orderItem from dataws and append special fields
   */
  public function createOrderItemImpuestos(){
    $dataws = $_SESSION['dataws'];
    $items = [];
    foreach($dataws as $field_name => $values){
      $order_item = OrderItem::create([
        'type' => 'impuestos_ot',  // Order Item Type
        'purchased_entity' => '2', // Must be string always - product id or instance
        'quantity' => 1,
        'unit_price' => new Price($values['monto'], 'USD'),  // Value take from dataws (monto)
        'overridden_unit_price' => TRUE,
      ]);
      $order_item->field_fecha = $values['fInicio'];
      $order_item->field_rubro = $values['rubro'];
      // $order_item->field_monto = $values['monto'];
      $order_item->field_servicio = $values['codSer'];
      $order_item->save();
      $items[] = $order_item;
    }
    return $items;
  }
  /**
   * Allow to create an order from code with custom fields using order item
   */
  public function createOrderCustom(){
    // $order_item = OrderItem::create([
    //   // 'type' => 'default',  // Order Item Type
    //   'type' => 'impuestos_ot',  // Order Item Type
    //   'purchased_entity' => '2', // Must be string always - product id or instance
    //   'quantity' => 1,
    //   // Omit these lines to preserve original product price.
    //   // 'unit_price' => new Price(80, 'USD'),
    //   // 'overridden_unit_price' => TRUE,
    //   // Add values for special fiels - Order Item
    //   'field_monto' => '40',
    //   'field_rubro' => 'AGUA',
    //   'field_servicio' => '55555'
    // ]);
    // $order_item->save();

    $entity_manager = \Drupal::entityTypeManager();
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $store = $entity_manager->getStorage('commerce_store')->load(1);
    $cart = $cart_provider->getCart('default', $store);
    if (!$cart) {
      $cart = $cart_provider->createCart('default', $store);
    }
    // $cart_manager->addOrderItem($cart, $order_item);
    foreach($this->createOrderItemImpuestos() as $field_name => $order_item){
      $cart_manager->addOrderItem($cart, $order_item);
    }


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
      'store_id' => 1,
      // 'order_items' => [$order_item],
      'order_items' => $this->createOrderItemImpuestos(),
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
    $allData = array();
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
