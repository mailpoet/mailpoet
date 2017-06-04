<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Model extends \Sudzy\ValidModel {
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

  function getErrors() {
    if(empty($this->_errors)) {
      return false;
    } else {
      return $this->_errors;
    }
  }

  function setError($error = '') {
    if(!empty($error)) {
      if(is_array($error)) {
        $this->_errors = array_merge($this->_errors, $error);
        $this->_errors = array_unique($this->_errors);
      } else {
        $this->_errors[] = $error;
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
              )
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
    $count = self::bulkAction($orm, function($ids) use($model) {
      $model::rawExecute(join(' ', array(
          'UPDATE `'.$model::$_table.'`',
          'SET `deleted_at` = NOW()',
          'WHERE `id` IN ('.rtrim(str_repeat('?,', count($ids)), ',').')'
        )),
        $ids
      );
    });

    return array('count' => $count);
  }

  static function bulkDelete($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use($model) {
      $model::whereIn('id', $ids)->deleteMany();
    });

    return array('count' => $count);
  }

  function restore() {
    return $this->set_expr('deleted_at', 'NULL')->save();
  }

  static function bulkRestore($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use($model) {
      $model::rawExecute(join(' ', array(
          'UPDATE `'.$model::$_table.'`',
          'SET `deleted_at` = NULL',
          'WHERE `id` IN ('.rtrim(str_repeat('?,', count($ids)), ',').')'
        )),
        $ids
      );
    });

    return array('count' => $count);
  }

  static function bulkAction($orm, $callback = false) {
    $total = $orm->count();

    if($total === 0) return false;

    $rows = $orm->select(static::$_table.'.id')
      ->offset(null)
      ->limit(null)
      ->findArray();

    $ids = array_map(function($model) {
      return (int)$model['id'];
    }, $rows);

    if($callback !== false) {
      $callback($ids);
    }

    // get number of affected rows
    return $orm->get_last_statement()->rowCount();
  }

  function duplicate($data = array()) {
    $model = get_called_class();
    $model_data = array_merge($this->asArray(), $data);
    unset($model_data['id']);

    $duplicate =  $model::create();
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
}