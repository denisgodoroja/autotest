<?php

/**
 * @file
 * Contains \Drupal\autotest\Classes\Autotest.
 */

namespace Drupal\autotest\Classes;

//use Drupal\Core\Database\Connection;
//use Drupal\Core\Database\Database;

class Autotest {
  const NUM_TESTS = 35;
  const NUM_QUESTIONS = 20;
  const DEFAULT_NUM_ANSWERS = 5;

  static protected $instance;

  protected $questions_cache = array();

  static public function getInstance() {
    if (!isset(static::$instance)) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  protected function __construct() {}

  public function start($test_number = 0) {
    $_SESSION['autotest'] = array(
      'start_time' => $_SERVER['REQUEST_TIME'],
      'end_time' => 0,
      'time_limit' => \Drupal::config('autotest.settings')->get('time_limit') * 60,
      'current' => 1,
      'questions' => array(),
    );

    $test_number = (int) max(0, min($test_number, self::NUM_TESTS));
    $path = drupal_get_path('module', 'autotest');
    $details = json_decode(file_get_contents($path . '/resources/tests.json'), TRUE);

    for ($question = 1; $question <= self::NUM_QUESTIONS; $question++) {
      $test = $test_number ? $test_number : mt_rand(1, self::NUM_TESTS);
      $_SESSION['autotest']['questions'][$question] = array(
        'test_number' => $test,
        'question_number' => $question,
        'num_answers' => self::DEFAULT_NUM_ANSWERS,
        'correct_answer' => empty($details[$test]['v' . $question]) ? 0 : $details[$test]['v' . $question],
        'filepath' => '/' . $path . '/resources/images/' . $test . '-' . $question . '.jpg',
        'answer' => 0,
        'answered_at' => 0,
      );
    }
  }

  public function end() {
    if (!empty($_SESSION['autotest']) && !$_SESSION['autotest']['end_time']) {
      $_SESSION['autotest']['end_time'] = $_SERVER['REQUEST_TIME'];
    }
  }

  public function isRunning() {
    if (!empty($_SESSION['autotest']) && $_SESSION['autotest']['start_time']) {
      if ($_SESSION['autotest']['time_limit']) {
        if ($_SERVER['REQUEST_TIME'] - $_SESSION['autotest']['start_time'] >= $_SESSION['autotest']['time_limit']) {
          $this->end();
        }
      }

      if (!$_SESSION['autotest']['end_time']) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function getQuestions() {
    return empty($_SESSION['autotest']['questions']) ? array() : $_SESSION['autotest']['questions'];
  }

  public function getQuestion($number) {
    if (!empty($_SESSION['autotest']['questions'][$number])) {
      return $this->overrideQuestion($_SESSION['autotest']['questions'][$number]);
    }
    return array();
  }

  public function getCurrentQuestionNumber() {
    return empty($_SESSION['autotest']['current']) ? 0 : $_SESSION['autotest']['current'];
  }

  public function getCurrentQuestion() {
    return $this->getQuestion($this->getCurrentQuestionNumber());
  }

  public function getTimeLeft() {
    if (empty($_SESSION['autotest']['time_limit'])) {
      return 0;
    }
    return $_SESSION['autotest']['time_limit'] - (time() - $_SESSION['autotest']['start_time']);
  }

  public function answer($answer_number, $question_number = 0) {
    if ($this->isRunning() && $answer_number) {
      if ($question_number) {
        if ($question_number >= 0 && $question_number <= self::NUM_QUESTIONS) {
          $_SESSION['autotest']['questions'][$question_number]['answer'] = $answer_number;
        }
      }
      else {
        $_SESSION['autotest']['questions'][$this->getCurrentQuestionNumber()]['answer'] = $answer_number;
      }
      return TRUE;
    }
    return FALSE;
  }

  public function nextQuestion($question_number = 0) {
    $question_number = max(0, min($question_number, self::NUM_QUESTIONS));
    if ($question_number) {
      $_SESSION['autotest']['current'] = $question_number;
    }
    elseif ($this->isRunning()) {
      do {
        $_SESSION['autotest']['current']++;
        if ($_SESSION['autotest']['current'] > self::NUM_QUESTIONS) {
          break;
        }
        $question = $this->getCurrentQuestion();
      }
      while ($question['answer']);

      if ($_SESSION['autotest']['current'] > self::NUM_QUESTIONS) {
        $_SESSION['autotest']['current'] = self::NUM_QUESTIONS;

        // End test only if all questions are answered
        $good_to_end = TRUE;
        foreach ($this->getQuestions() as $question) {
          if (!$question['answer']) {
            $good_to_end = FALSE;
            break;
          }
        }

        if ($good_to_end) {
          $this->end();
        }
      }
    }
    else {
      $_SESSION['autotest']['current']++;
      if ($_SESSION['autotest']['current'] > self::NUM_QUESTIONS) {
        $_SESSION['autotest']['current'] = self::NUM_QUESTIONS;
      }
    }
  }

  public function overrideQuestion($question, $reset = FALSE) {
    $id = $this->buildQuestionId($question);

    if (!isset($this->questions_cache[$id]) || $reset) {
      $connection = \Drupal::database();
      $data = $connection->select('autotest_alters', 'a')
        ->fields('a')
        ->condition('question', $id)
        ->execute()
        ->fetchAssoc();
      if ($data) {
        $this->questions_cache[$id] = array_merge($question, $data);
      }
      else {
        $this->questions_cache[$id] = FALSE;
      }
    }

    return $this->questions_cache[$id] ? $this->questions_cache[$id] : $question;
  }

  public function saveQuestion($question) {
    $fields = array(
      'correct_answer' => $question['correct_answer'],
      'num_answers' => $question['num_answers'],
      'explanation' => $question['explanation'],
    );
    if (empty($question['question'])) {
      $fields['question'] = $this->buildQuestionId($question);
      $connection = \Drupal::database();
      $connection->insert('autotest_alters')
        ->fields($fields)
        ->execute();
    }
    else {
      $connection = \Drupal::database();
      $connection->update('autotest_alters')
        ->fields($fields)
        ->condition('question', $question['question'])
        ->execute();
    }
  }

  public function resetQuestion($question) {
    if (empty($question['question'])) {
      return FALSE;
    }
    $connection = \Drupal::database();
    $connection->delete('autotest_alters')
      ->condition('question', $question['question'])
      ->execute();
  }

  protected function buildQuestionId($question) {
    return $question['test_number'] . '-' . $question['question_number'];
  }
}
