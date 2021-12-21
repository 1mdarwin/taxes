<?php

namespace Drupal\taxes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    //  dsm($_POST);

    // dpm($_SESSION['taxes']);
    $ced = $_POST['cedula'];

    $header = [
      'codSer' => $this->t('Codigo de Servicio'),
      'rubro'   => $this->t('Rubro'),
      'fInicio' => $this->t('Fecha de Servicio'),
      'fFin'    => $this->t('Fecha de Expiración'),
      'monto'   => $this->t('Valor'),
    ];
    print_r($_SESSION['dataws']);

    if(empty($_SESSION['dataws'])){
      $_SESSION['dataws'] = NULL;
    }else{
      $dataws = $_SESSION['dataws'];  // Recover data from session
    }
    print_r($dataws);
    asort($dataws);
    $options = $this->reindexKey($dataws, false);

    $form['impuestos'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Impuestos'),
      '#header' => $header,
      '#options' => $options,
      '#description' => $this->t('impuestos a presentar'),
      '#weight' => '0',
    ];
    $form['submit'] = [
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
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
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
