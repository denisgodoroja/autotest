<?php

namespace Drupal\autotest\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a test form object.
 */
class AutotestSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'autotest_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('autotest.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autotest.settings');

    $form['time_limit'] = array(
      '#type' => 'select',
      '#title' => t('Time limit (minutes)'),
      '#options' => array_combine(range(1, 60), range(1, 60)),
      '#default_value' => $config->get('time_limit'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('autotest.settings');
    $config
      ->set('time_limit', $form_state->getValue('time_limit'))
      ->save();
    drupal_set_message($this->t('The configuration has been saved!'));
  }
}
