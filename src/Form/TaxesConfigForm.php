<?php

namespace Drupal\taxes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TaxesConfigForm.
 */
class TaxesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'taxes.taxesconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxes_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('taxes.taxesconfig');
    $form['creditcard'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payments with credit card'),
      '#description' => $this->t('Payments with credit card container'),
    ];
    $form['creditcard']['payments_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow to payments only administrator role'),
      '#description' => $this->t('If enabled this feature, only the administrator role can make payments'),
      '#default_value' => $config->get('payments_all'),
    ];
    $form['endofyear'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled/disabled query taxes'),
      '#description' => $this->t('Enabled/disabled query taxes during end of year'),
    ];
    $form['endofyear']['taxesquery'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disabled taxes query'),
      '#description' => $this->t('If enabled this feature, the taxes query will be disabled'),
      '#default_value' => $config->get('taxesquery'),
    ];
    $form['endofyear']['endofyear_message'] = [
	    '#type' => 'textarea',
	    '#title' => $this->t('End of year message'),
	    '#description' => $this->t('Welcome message display to users when they login'),
	    '#default_value' => $config->get('endofyear_message'),
	  ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('taxes.taxesconfig')
      ->set('payments_all', $form_state->getValue('payments_all'))
      ->set('endofyear_message', $form_state->getValue('endofyear_message'))
      ->set('taxesquery', $form_state->getValue('taxesquery'))
      ->save();
  }

}
