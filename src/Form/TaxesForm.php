<?php

namespace Drupal\taxes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class TaxesForm.
 */
class TaxesForm extends FormBase {
  /**
   * @var \Drupal\taxes\ImpuestosClient
   */
  protected $impuestosClient;

  /**
   * constructor
   * @param \Drupal\taxes\ImpuestosClient $impuestos
   */
  public function __construct($impuestos){
    $this->impuestosClient = $impuestos;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container){
    return new static (
      $container->get('impuestos_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // dsm($form);
    $config = \Drupal::config('taxes.taxesconfig');
    if ($config->get('taxesquery') == 1){
      $response = new RedirectResponse('consultam');
      $response->send();
    }
    $popularTaxes = [
      "TODOS" => "TODOS",
      "SERVICIO DE AGUA POTABLE" => "SERVICIO DE AGUA POTABLE",
      "PREDIO URBANO" => "PREDIO URBANO",
      "PATENTE MUNICIPAL" => "PATENTE MUNICIPAL",
      "ALCABALAS" => "ALCABALAS",
      "UTILIDAD EN LA VENTA DE PREDIOS" => "UTILIDAD EN LA VENTA DE PREDIOS",
      "PREDIO RUSTICO" => "PREDIO RUSTICO",
      "IMPUESTO A LOS ACTIVOS TOTALES" => "IMPUESTO A LOS ACTIVOS TOTALES",
      "ARR. BODEGAS,EDIF,LOCALES,TIERRAS" => "ARR. BODEGAS,EDIF,LOCALES,TIERRAS",
      "CEM (OTRAS CONTRIBUCIONES) GLOBALES" => "CEM (OTRAS CONTRIBUCIONES) GLOBALES",
      "PAVIMENTACION CIUDAD ALEGRIA" => "PAVIMENTACION CIUDAD ALEGRIA",
      "PAVIMENTACION PITAS I" => "PAVIMENTACION PITAS I",
      "SOLARES NO EDIFICADOS" => "SOLARES NO EDIFICADOS",
      "USO Y ESTACIONAMIENTO TERMINAL TERR" => "USO Y ESTACIONAMIENTO TERMINAL TERR",
      "SERVICIOS TECNICOS Y ADMINISTRATIVO" => "SERVICIOS TECNICOS Y ADMINISTRATIVO",
      "MULTAS PATENTE MUNICIPAL" => "MULTAS PATENTE MUNICIPAL"
    ];
    $form['consulta'] = [
      '#title' => $this->t('Datos del abonado'),
      '#type' => 'fieldset',
      '#description' => $this->t('Consulta a través de su cédula y por RUBRO'),
    ];

    $form['consulta']['cedula'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cedula'),
      '#description' => $this->t('Cedula del contribuyente'),
      '#maxlength' => 13,
      '#size' => 13,
      '#weight' => '0',
    ];

    $form['consulta']['rubro'] = [
      '#type' => 'select',
      '#title' => $this->t('Rubro'),
      '#description' => $this->t('Rubro a pagar'),
      '#options' => $popularTaxes,
      '#default_value' => $this->t('TODOS'),
      //'#size' => 5,
      '#weight' => '0',
    ];

    $form['consulta']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    unset($_SESSION['dataws']); // Unset Session with data
    $_SESSION['post'] = $_POST; // Catch values from captcha control
    foreach ($form_state->getValues() as $key => $value) {
      // \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format' ? $value['value']: $value));

      if($key === 'cedula'){
        $ced = $value;
      }
      if($key === 'rubro'){
        $rubro = $value;
      }
    }
    $response = $this->impuestosClient->withCedula($ced); // Call service method with DNI parameter

    $_SESSION['dataws'] = $this->filterTaxes($response, $rubro);
    $_SESSION['contribuyente'] = $response['taxpayer']['firstName'];
    $_SESSION['cedula'] = $ced;
    $_SESSION['rubro'] = $rubro;

    // $_SESSION['rubro'] = $rubro;
    // $form_state->setRedirect('taxes.resconsulta'); // NO tableselect result
    $form_state->setRedirect('taxes.ts_resultado');
  }

  /**
   *
   * Filter values with specific role
   * @param Array taxes
   * @return Array
   */
  public function filterTaxes ($taxes, $rubro){
    global $user, $base_url;
    if( empty($_POST['g-recaptcha-response'])){
            //dsm('captcha vacio');
    }
    // if(!user_is_logged_in()){
    // if(empty($ced)|| !is_numeric($ced) || strlen($_POST['g-recaptcha-response'])==0){
    if(empty($taxes)){
      $response = new RedirectResponse('/taxes/consulta');
      $response->send();
      return;
    }

    $result = array();
    $patente_list = array(
      "PATENTE MUNICIPAL",
      "IMPUESTO A LOS ACTIVOS TOTALES",
      "MULTAS PATENTE MUNICIPAL"
    );
    $agua_list = array(
      "SERVICIO DE AGUA POTABLE",
      "CEM (OTRAS CONTRIBUCIONES) GLOBALES"
    );
    $alcabala_list = array(
      "ALCABALAS",
      "UTILIDAD EN LA VENTA DE PREDIOS"
    );

    // Asign selected array to another array
    switch ($rubro) {
      case 'SERVICIO DE AGUA POTABLE':
        $rango_impuestos = $agua_list;
        break;
      case 'PATENTE MUNICIPAL':
        $rango_impuestos = $patente_list;
        break;
      case 'ALCABALAS':
        $rango_impuestos = $alcabala_list;
        break;

      default:
        $rango_impuestos = array($rubro => $rubro);
        break;
    }
    // dsm($rango_impuestos);
    $alltaxes = [];
    $par = 1;
    if(isset($taxes['bonds'])){
      $numItems = sizeof($taxes['bonds']);
      if ($rubro == 'TODOS'){
        $alltaxes = $this->fill_taxes($taxes['bonds']);
        // dsm($alltaxes);
      }else{
        if ($numItems == 1){
          if(in_array($taxes['bonds']['account'], $rango_impuestos)){
            $alltaxes[$par] = array(
            'codSer'  => $taxes['bonds']['serviceCode'],
            'rubro'   => $taxes['bonds']['account'],
            'fInicio' => substr($taxes['bonds']['serviceDate'], 0, 10),
            'fFin'    => substr($taxes['bonds']['expirationDate'], 0, 10),
            'monto'   => $taxes['bonds']['total'],
            'id'      => $taxes['bonds']['id'], // Bond's Id for register payment
            'number'      => $taxes['bonds']['number'], // Bond's Id for register payment
            'interes' => 0,
            'recargo' => 0,
            'sku'     => $par,
            );
            $par+=1;
          }

        }else{ // More than one element
          for($i=0; $i<$numItems; $i++){
            // dsm($i);
            #$remision = json_decode($result->return->bonds[$i]->metadata, false);
            if(isset($taxes['bonds'][$i]['serviceCode'])){
                    $srvcode = substr($taxes['bonds'][$i]['serviceCode'],0,15);
            }else{
                    $srvcode = '';
            }
            if(in_array($taxes['bonds'][$i]['account'], $rango_impuestos)){
              $alltaxes[$par] = array(
                'codSer'  => $srvcode,
                'rubro'   => $taxes['bonds'][$i]['account'],
                'fInicio' => substr($taxes['bonds'][$i]['serviceDate'], 0, 10),
                'fFin'    => substr($taxes['bonds'][$i]['expirationDate'], 0, 10),
                'monto'   => $taxes['bonds'][$i]['total'],
                'id'  => $taxes['bonds'][$i]['id'],
                'number'  => $taxes['bonds'][$i]['number'],
                'interes' => 0,
                'recargo' => 0,
                'sku'     => $par,
              );
              $par+=1;
            }

          }// end for
        }
      }
    }else{
      // No available values for payments
    }

    return $alltaxes;
  }
  /**
   * Show all taxes doesn't matter the role
   * @param Array taxes
   * @return Array alltaxes
   */
  public function fill_taxes($bonds){
    $par = 1;
    $numItems = sizeof($bonds);
    if($numItems == 1){
      $alltaxes[$par] = array(
        'codSer'  => $bonds['serviceCode'],
        'rubro'   => $bonds['account'],
        'fInicio' => substr($bonds['serviceDate'], 0, 10),
        'fFin'    => substr($bonds['expirationDate'], 0, 10),
        'monto'   => $bonds['total'],
        'id'      => $bonds['id'], // Bond's Id for register payment
        'number'      => $bonds['number'], // Bond's Id for register payment
        'interes' => 0,
        'recargo' => 0,
        'sku'     => $par,
        );
      $par+=1;
    }
    // More than one element
    else{
      for($i=0; $i<$numItems; $i++){
        if(isset($bonds[$i]['serviceCode'])){
          $srvcode = substr($bonds[$i]['serviceCode'],0,15);
        }else{
          $srvcode = '';
        }
        $alltaxes[$par] = array(
          'codSer'  => $srvcode,
          'rubro'   => $bonds[$i]['account'],
          'fInicio' => substr($bonds[$i]['serviceDate'], 0, 10),
          'fFin'    => substr($bonds[$i]['expirationDate'], 0, 10),
          'monto'   => $bonds[$i]['total'],
          'id'  => $bonds[$i]['id'],
          'number'  => $bonds[$i]['number'],
          'interes' => 0,
          'recargo' => 0,
          'sku'     => $par,
        );
        $par+=1;

      }// end for
    }

    return $alltaxes;
  }

}
