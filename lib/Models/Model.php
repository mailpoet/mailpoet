<?php

namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Model extends \Sudzy\ValidModel {
  const DUPLICATE_RECORD = 23000;

  protected $_errors;
  protected $_new_record;

  function __construct() {
    $this->_errors = array();
    $validator = new ModelValidator();
    parent::__construct($validator);
  }

  static function create() {
    return parent::create();
  }

  /**
   * Creates a row, or updates it if already exists. It tries to find the existing 
   * row by `id` (if given in `$data`), or by the given `$keys`. If `$onCreate` is 
   * given, it's used to transform `$data` before creating the new row.
   * 
   * @param  array   $data
   * @param  boolean $keys
   * @param  callable $onCreate
   * @return self
   */
  static protected function _createOrUpdate($data = array(), $keys = false, $onCreate = false) {
    $model = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $model = static::findOne((int)$data['id']);
    }

    if(!empty($keys)) {
      $first = true;
      foreach($keys as $field => $value) {
        if($first) {
          $model = static::where($field, $value);
          $first = false;
        } else {
          $model = $model->where($field, $value);
        }
      }
      $model = $model->findOne();
    }

    if($model === false) {
      if(!empty($onCreate)) {
        $data = $onCreate($data);
      }
      $model = static::create();
      $model->hydrate($data);
    } else {
      unset($data['id']);
      $model->set($data);
    }

    return $model->save();
  }

  static public function createOrUpdate($data = array()) {
    return self::_createOrUpdate($data);
  }

  function getErrors() {
    if(empty($this->_errors)) {
      return false;
    } else {
      return $this->_errors;
    }
  }

  function setError($error = '', $error_code = null) {
    if(!$error_code) {
      $error_code = count($this->_errors);
    }
    if(!empty($error)) {
      if(is_array($error)) {
        $this->_errors = array_merge($this->_errors, $error);
        $this->_errors = array_unique($this->_errors);
      } else {
        $this->_errors[$error_code] = $error;
      }
    }
  }

  function save() {
    $this->setTimestamp();
    $this->_new_record = $this->isNew();
    try {
      parent::save();
    } catch(\Sudzy\ValidationException $e) {
      $this->setError($e->getValidationErrors());
    } catch(\PDOException $e) {
      switch($e->getCode()) {
        case 23000:
          preg_match("/for key \'(.*?)\'/i", $e->getMessage(), $matches);
          if(isset($matches[1])) {
            $column = $matches[1];
            $this->setError(
              sprintf(
                __('Another record already exists. Please specify a different "%1$s".', 'mailpoet'),
                $column
              ),
              Model::DUPLICATE_RECORD
            );
          } else {
            $this->setError($e->getMessage());
          }
          break;
        default:
          $this->setError($e->getMessage());
      }
    }
    return $this;
  }

  function isNew() {
    return (isset($this->_new_record)) ?
      $this->_new_record :
      parent::isNew();
  }

  function trash() {
    return $this->set_expr('deleted_at', 'NOW()')->save();
  }

  static function bulkTrash($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use ($model) {
      $model::rawExecute(join(' ', array(
        'UPDATE `' . $model::$_table . '`',
        'SET `deleted_at` = NOW()',
        'WHERE `id` IN (' . rtrim(str_repeat('?,', count($ids)), ',') . ')'
      )), $ids);
    });

    return array('count' => $count);
  }

  static function bulkDelete($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use ($model) {
      $model::whereIn('id', $ids)->deleteMany();
    });

    return array('count' => $count);
  }

  function restore() {
    return $this->set_expr('deleted_at', 'NULL')->save();
  }

  static function bulkRestore($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use ($model) {
      $model::rawExecute(join(' ', array(
        'UPDATE `' . $model::$_table . '`',
        'SET `deleted_at` = NULL',
        'WHERE `id` IN (' . rtrim(str_repeat('?,', count($ids)), ',') . ')'
      )), $ids);
    });

    return array('count' => $count);
  }

  static function bulkAction($orm, $callback = false) {
    $total = $orm->count();

    if($total === 0) return false;

    $rows = $orm->select(static::$_table . '.id')
      ->offset(null)
      ->limit(null)
      ->findArray();

    $ids = array_map(function($model) {
      return (int)$model['id'];
    }, $rows);

    if(is_callable($callback)) {
      $callback($ids);
    }

    // get number of affected rows
    return $orm->get_last_statement()
      ->rowCount();
  }

  function duplicate($data = array()) {
    $model = get_called_class();
    $model_data = array_merge($this->asArray(), $data);
    unset($model_data['id']);

    $duplicate = $model::create();
    $duplicate->hydrate($model_data);
    $duplicate->set_expr('created_at', 'NOW()');
    $duplicate->set_expr('updated_at', 'NOW()');
    $duplicate->set_expr('deleted_at', 'NULL');

    $duplicate->save();
    return $duplicate;
  }

  function setTimestamp() {
    if($this->created_at === null) {
      $this->set_expr('created_at', 'NOW()');
    }
  }

  static function getPublished() {
    return static::whereNull('deleted_at');
  }

  static function getTrashed() {
    return static::whereNotNull('deleted_at');
  }

  /**
   * PHP 5.3 fix for incorrectly returned model results when using asArray() function.
   * Jira reference: https://goo.gl/UZaMj5
   * TODO: remove after phasing out PHP 5.3 support
   */
  function asArray() {
    return call_user_func_array('parent::as_array', func_get_args());
  }

  /**
   * Rethrow PDOExceptions to prevent exposing sensitive data in stack traces
   */
  public static function __callStatic($method, $parameters) {
    try {
      return parent::__callStatic($method, $parameters);
    } catch(\PDOException $e) {
      throw new \Exception($e->getMessage());
    }
  }

  public function validate() {
    $success = true;
    foreach(array_keys($this->_validations) as $field) {
      $success = $success && $this->validateField($field, $this->$field);
    }
    $this->setError($this->getValidationErrors());
    return $success;
  }
}