<?php

/**
 * @file
 * Contains \Drupal\autotest\Controller\AutotestController.
 */

namespace Drupal\autotest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\autotest\Classes\Autotest;

class AutotestController extends ControllerBase {

  public function mainTitle() {
    $autotest = Autotest::getInstance();
    if ($autotest->isRunning()) {
      return $this->questionTitle($autotest->getCurrentQuestionNumber());
    }
    return $this->t('Autotest');
  }

  function questionTitle($question_number) {
    $autotest = Autotest::getInstance();
    $question = $autotest->getQuestion($question_number);
    $args = array(
      '@number' => $question['question_number'],
      '@test' => $question['test_number'],
    );
    return $this->t('Question @number (from test @test)', $args);
  }

  public function mainPage() {
    $autotest = Autotest::getInstance();
    $build = array();
    $build['#cache']['max-age'] = 0;
    $build['#attached']['library'][] = 'autotest/autotest.css';
    $build['results'] = $this->buildResults($autotest);

    if ($autotest->isRunning()) {
      // Render question
      $build['#attached']['drupalSettings']['autotest'] = array(
        'time_left' => $autotest->getTimeLeft(),
      );
      $build['#attached']['library'][] = 'autotest/autotest.js';

      $build['question'] = array(
        '#theme' => 'question',
        '#question' => $autotest->getCurrentQuestion(),
        '#time_left' => $autotest->getTimeLeft(),
        '#is_running' => $autotest->isRunning(),
      );

      if (\Drupal::currentUser()->hasPermission('autotest admin')) {
        $build['alter'] = \Drupal::formBuilder()->getForm('Drupal\autotest\Form\AutotestAlterForm', $autotest, $autotest->getCurrentQuestion());
      }
    }
    else {
      $list = array();
      $list[] = array(
        '#type' => 'link',
        '#title' => $this->t('Start random test'),
        '#url' => Url::fromRoute('autotest.start'),
      );
      for ($i = 1; $i <= Autotest::NUM_TESTS; $i++) {
        $list[] = array(
          '#type' => 'link',
          '#title' => $this->t('Start test @i', array('@i' => $i)),
          '#url' => Url::fromRoute('autotest.start_test', array('test_number' => $i)),
        );
      }

      $build['start_test'] = array(
        '#theme' => 'item_list',
        '#items' => $list,
        '#attributes' => array(
          'class' => 'start-test',
        ),
      );
    }

    return $build;
  }

  protected function buildResults(Autotest $autotest, $with_home = FALSE) {
    $questions = $autotest->getQuestions();
    if ($questions) {
      $header = array();
      $row = array();
      $correct = array(
        '#markup' => $this->t('Correct'),
        '#prefix' => '<span class="correct">',
        '#suffix' => '</span>',
      );
      $wrong = array(
        '#markup' => $this->t('Wrong'),
        '#prefix' => '<span class="wrong">',
        '#suffix' => '</span>',
      );

      if ($with_home) {
        $header[] = array(
          'data' => array(
            '#type' => 'link',
            '#title' => (string) $this->t('To main page'),
            '#url' => Url::fromRoute('autotest.main'),
          ),
        );
        $row[] = '';
      }

      foreach ($questions as $i => $question) {
        $header[] = array(
          'data' => array(
            '#type' => 'link',
            '#title' => $i,
            '#url' => Url::fromRoute('autotest.question', array('question_number' => $i)),
          ),
        );
        if ($question['answer']) {
          $row[] = array(
            'data' => ($question['answer'] == $question['correct_answer']) ? $correct : $wrong,
          );
        }
        else {
          $row[] = '';
        }
      }

      return array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => array($row),
        '#attributes' => array(
          'class' => array('autotest-results'),
        ),
      );
    }

    return NULL;
  }

  function startPage($test_number = 0) {
    $autotest = Autotest::getInstance();

    if ($autotest->isRunning()) {
      drupal_set_message($this->t('You have already started a test.'), 'error');
    }
    else {
      $autotest->start($test_number);
    }

    return $this->redirect('autotest.main');
  }

  function endPage() {
    $autotest = Autotest::getInstance();
    if ($autotest->isRunning()) {
      $autotest->end();
    }
    else {
      drupal_set_message($this->t('You have already ended a test.'), 'error');
    }
    return $this->redirect('autotest.main');
  }

  function answerPage($answer_number) {
    $autotest = Autotest::getInstance();
    if ($autotest->isRunning()) {
      $answer_number = (int) $answer_number;
      if ($autotest->answer($answer_number)) {
        $question = $autotest->getCurrentQuestion();
        if ($answer_number == $question['correct_answer']) {
          $autotest->nextQuestion();
          drupal_set_message($this->t('You have answered correctly'));
        }
        else {
          drupal_set_message($this->t('The correct answer is @i', array('@i' => $question['correct_answer'])), 'warning');
          if (!empty($question['explanation'])) {
            drupal_set_message($question['explanation'], 'warning');
          }
        }
      }
      else {
        drupal_set_message(t('Invalid answer. Try again'), 'error');
      }
    }

    return $this->redirect('autotest.main');
  }

  function nextPage() {
    $autotest = Autotest::getInstance();
    $autotest->nextQuestion();
    if ($autotest->isRunning()) {
      return $this->redirect('autotest.main');
    }
    else {
      return $this->redirect('autotest.question', array('question_number' => $autotest->getCurrentQuestionNumber()));
    }
  }

  function questionPage($question_number) {
    $autotest = Autotest::getInstance();
    $autotest->nextQuestion($question_number);
    if ($autotest->isRunning()) {
      return $this->redirect('autotest.main');
    }

    $build = array();
    $build['#cache']['max-age'] = 0;
    $build['#attached']['library'][] = 'autotest/autotest.css';
    $build['results'] = $this->buildResults($autotest, TRUE);
    $build['question'] = array(
      '#theme' => 'question',
      '#question' => $autotest->getQuestion($question_number),
      '#time_left' => 0,
      '#is_running' => FALSE,
    );

    return $build;
  }
}
