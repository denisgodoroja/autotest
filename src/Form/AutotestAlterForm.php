<?php

namespace Drupal\autotest\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\autotest\Classes\Autotest;

/**
 * Provides a test form object.
 */
class AutotestAlterForm extends FormBase {

  /** @var \Drupal\autotest\Classes\Autotest */
  protected $autotest;

  protected $question;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'autotest_alter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Autotest $autotest = NULL, array $question = NULL) {
    $this->autotest = $autotest;
    $this->question = $question;

    $form['correct_answer'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Correct answer'),
      '#options' => array_combine(range(1, 5), range(1, 5)),
      '#default_value' => $this->question['correct_answer'],
    );

    $form['num_answers'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Number of answers'),
      '#options' => array_combine(range(1, 5), range(1, 5)),
      '#default_value' => $this->question['num_answers'],
    );

    $form['explanation'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Explanation'),
      '#rows' => 3,
      '#resizable' => FALSE,
      '#default_value' => empty($this->question['explanation']) ? '' : $this->question['explanation'],
    );

    $form['actions'] = array(
      '#type' => 'action',
    );

    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    $form['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('correct_answer') > $form_state->getValue('num_answers')) {
      $form_state->setErrorByName('correct_answer', $this->t('The correct answer must not be higher than the number of answers.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      if ($form_state->getValue('op')->getUntranslatedString() == 'Reset') {
        $this->autotest->resetQuestion($this->question);
      }
      else {
        $this->question['correct_answer'] = $form_state->getValue('correct_answer');
        $this->question['num_answers'] = $form_state->getValue('num_answers');
        $this->question['explanation'] = $form_state->getValue('explanation');
        $this->autotest->saveQuestion($this->question);
        drupal_set_message($this->t('The question been saved successfully'));
      }
    }
    catch (Exception $e) {
      drupal_set_message(t('Failed to save the question'), 'error');
    }
  }
}
