<?php

namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @method static array|string getConfig($key = null, $connection_name = self::DEFAULT_CONNECTION)
 * @method static null resetConfig()
 * @method static self forTable($table_name, $connection_name = self::DEFAULT_CONNECTION)
 * @method static null setDb($db, $connection_name = self::DEFAULT_CONNECTION)
 * @method static null resetDb()
 * @method static null setupLimitClauseStyle($connection_name)
 * @method static \PDO getDb($connection_name = self::DEFAULT_CONNECTION)
 * @method static bool rawExecute($query, $parameters = array())
 * @method static \PDOStatement getLastStatement()
 * @method static string getLastQuery($connection_name = null)
 * @method static array getQueryLog($connection_name = self::DEFAULT_CONNECTION)
 * @method array getConnectionNames()
 * @method $this useIdColumn($id_column)
 * @method $this|false findOne($id=null)
 * @method static static|false findOne($id=null)
 * @method array findMany()
 * @method static array findMany()
 * @method \MailPoetVendor\Idiorm\IdiormResultSet findResultSet()
 * @method array findArray()
 * @method static array findArray()
 * @method $this forceAllDirty()
 * @method $this select_expr(string $expr, string $alias=null)
 * @method $this rawQuery($query, $parameters = array())
 * @method static $this rawQuery($query, $parameters = array())
 * @method $this tableAlias($alias)
 * @method static $this tableAlias($alias)
 * @method int countNullIdColumns()
 * @method $this select($column, $alias=null)
 * @method static $this select($column, $alias=null)
 * @method $this selectExpr($expr, $alias=null)
 * @method static $this selectExpr($expr, $alias=null)
 * @method $this selectMany(...$values)
 * @method static static selectMany(...$values)
 * @method static selectManyExpr($values)
 * @method $this rawJoin(string $table, string|array $constraint, string $table_alias, array $parameters = array())
 * @method $this innerJoin(string $table, string|array $constraint, string $table_alias=null)
 * @method $this join(string $table, string|array $constraint, string $table_alias=null)
 * @method static static join(string $table, string|array $constraint, string $table_alias=null)
 * @method $this leftOuterJoin(string $table, string|array $constraint, string $table_alias=null)
 * @method $this rightOuterJoin(string $table, string|array $constraint, string $table_alias=null)
 * @method $this fullOuterJoin(string $table, string|array $constraint, string $table_alias=null)
 * @method $this where($column_name, $value=null)
 * @method static $this where($column_name, $value=null)
 * @method $this whereEqual($column_name, $value=null)
 * @method static $this whereEqual($column_name, $value=null)
 * @method $this whereNotEqual($column_name, $value=null)
 * @method static $this whereNotEqual($column_name, $value=null)
 * @method $this whereIdIs($id)
 * @method $this whereAnyIs($values, $operator='=')
 * @method static $this whereAnyIs($values, $operator='=')
 * @method $this whereIdIn($ids)
 * @method static static whereIdIn($ids)
 * @method $this whereLike($column_name, $value=null)
 * @method static $this whereLike($column_name, $value=null)
 * @method $this whereNotLike($column_name, $value=null)
 * @method $this whereGt($column_name, $value=null)
 * @method static $this whereGt($column_name, $value=null)
 * @method static $this whereLt($column_name, $value=null)
 * @method $this whereGte($column_name, $value=null)
 * @method $this whereLte($column_name, $value=null)
 * @method $this whereIn($column_name, $values)
 * @method static $this whereIn($column_name, $values)
 * @method $this whereNotIn($column_name, $values)
 * @method static $this whereNotIn($column_name, $values)
 * @method $this whereNull($column_name)
 * @method static static whereNull($column_name)
 * @method $this whereNotNull($column_name)
 * @method static $this whereNotNull($column_name)
 * @method $this whereRaw($clause, $parameters=array())
 * @method static $this whereRaw($clause, $parameters=array())
 * @method $this deleteMany()
 * @method static $this deleteMany()
 * @method $this orderByDesc($column_name)
 * @method static $this orderByDesc($column_name)
 * @method $this orderByAsc($column_name)
 * @method static $this orderByAsc($column_name)
 * @method $this orderByExpr($clause)
 * @method $this groupBy($column_name)
 * @method $this groupByExpr($expr)
 * @method $this havingEqual($column_name, $value=null)
 * @method $this havingNotEqual($column_name, $value=null)
 * @method $this havingIdIs($id)
 * @method $this havingLike($column_name, $value=null)
 * @method $this havingNotLike($column_name, $value=null)
 * @method $this havingGt($column_name, $value=null)
 * @method $this havingLt($column_name, $value=null)
 * @method $this havingGte($column_name, $value=null)
 * @method $this havingLte($column_name, $value=null)
 * @method $this havingIn($column_name, $values=null)
 * @method $this havingNotIn($column_name, $values=null)
 * @method $this havingNull($column_name)
 * @method $this havingNotNull($column_name)
 * @method $this havingRaw($clause, $parameters=array())
 * @method static $this clearCache($table_name = null, $connection_name = self::DEFAULT_CONNECTION)
 * @method bool setExpr($key, $value = null)
 * @method bool isDirty($key)
 * @method static static filter(...$args)
 * @method array asArray(...$args)
 * @method $this hasMany($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_current_models_table=null, $connection_name=null)
 * @method $this hasManyThrough($associated_class_name, $join_class_name=null, $key_to_base_table=null, $key_to_associated_table=null,  $key_in_base_table=null, $key_in_associated_table=null, $connection_name=null)
 * @method mixed hasOne($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_current_models_table=null, $connection_name=null)
 * @method $this|bool create($data=null)
 * @method static $this|bool create($data=null)
 * @method int count()
 * @method static int count()
 * @method int sum($column_name)
 * @method int min($column_name)
 * @method int max($column_name)
 * @method int avg($column_name)
 * @method static int sum($column_name)
 * @method static int min($column_name)
 * @method static int max($column_name)
 * @method static int avg($column_name)
 * @method static static limit(int $limit)
 * @method static static distinct()
 * @method $this set(string|array $key, string|null $value = null)
 *
 * @property string|null $createdAt
 * @property string|null $updatedAt
 * @property string|null $id
 * @property string|null $first
 * @property string|null $last
 */

class Model extends \MailPoetVendor\Sudzy\ValidModel {
  const DUPLICATE_RECORD = 23000;

  public static $_table;
  protected $_errors;
  protected $newRecord;

  public function __construct() {
    $this->_errors = [];
    $validator = new ModelValidator();
    parent::__construct($validator);
  }

  /**
   * @return static
   */
  public static function create() {
    $created = parent::create();
    if (is_bool($created)) {
      throw new \Exception('ORM is not initialised');
    }
    return $created;
  }

  /**
   * Creates a row, or updates it if already exists. It tries to find the existing
   * row by `id` (if given in `$data`), or by the given `$keys`. If `$onCreate` is
   * given, it's used to transform `$data` before creating the new row.
   *
   * @param  array   $data
   * @param  array|boolean $keys
   * @param  callable|bool $onCreate
   * @return self
   */
  static protected function _createOrUpdate($data = [], $keys = false, $onCreate = false) {
    $model = false;

    if (isset($data['id']) && (int)$data['id'] > 0) {
      $model = static::findOne((int)$data['id']);
    }

    if ($model === false && !empty($keys)) {
      foreach ($keys as $field => $value) {
        if ($model === false) {
          $model = static::where($field, $value);
        } else {
          $model = $model->where($field, $value);
        }
      }
      if ($model) $model = $model->findOne();
    }

    if ($model === false) {
      if (!empty($onCreate) && is_callable($onCreate)) {
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

  static public function createOrUpdate($data = []) {
    return self::_createOrUpdate($data);
  }

  public function getErrors() {
    if (empty($this->_errors)) {
      return false;
    } else {
      return $this->_errors;
    }
  }

  public function setError($error = '', $errorCode = null) {
    if (!$errorCode) {
      $errorCode = count($this->_errors);
    }
    if (!empty($error)) {
      if (is_array($error)) {
        $this->_errors = array_merge($this->_errors, $error);
        $this->_errors = array_unique($this->_errors);
      } else {
        $this->_errors[$errorCode] = $error;
      }
    }
  }

  public function save() {
    $this->setTimestamp();
    $this->newRecord = $this->isNew();
    try {
      parent::save();
    } catch (\MailPoetVendor\Sudzy\ValidationException $e) {
      $this->setError($e->getValidationErrors());
    } catch (\PDOException $e) {
      switch ($e->getCode()) {
        case 23000:
          preg_match("/for key '(?:.*\.)*(.*?)'/i", $e->getMessage(), $matches);
          if (isset($matches[1])) {
            $column = $matches[1];
            $this->setError(
              sprintf(
                WPFunctions::get()->__('Another record already exists. Please specify a different "%1$s".', 'mailpoet'),
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

  public function isNew() {
    return (isset($this->newRecord)) ?
      $this->newRecord :
      parent::isNew();
  }

  public function trash() {
    return $this->set_expr('deleted_at', 'NOW()')->save();
  }

  public static function bulkTrash($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use ($model) {
      $model::rawExecute(join(' ', [
        'UPDATE `' . $model::$_table . '`',
        'SET `deleted_at` = NOW()',
        'WHERE `id` IN (' . rtrim(str_repeat('?,', count($ids)), ',') . ')',
      ]), $ids);
    });

    return ['count' => $count];
  }

  public static function bulkDelete($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use ($model) {
      $model::whereIn('id', $ids)->deleteMany();
    });

    return ['count' => $count];
  }

  public function restore() {
    return $this->set_expr('deleted_at', 'NULL')->save();
  }

  public static function bulkRestore($orm) {
    $model = get_called_class();
    $count = self::bulkAction($orm, function($ids) use ($model) {
      $model::rawExecute(join(' ', [
        'UPDATE `' . $model::$_table . '`',
        'SET `deleted_at` = NULL',
        'WHERE `id` IN (' . rtrim(str_repeat('?,', count($ids)), ',') . ')',
      ]), $ids);
    });

    return ['count' => $count];
  }

  public static function bulkAction($orm, $callback = false) {
    $total = $orm->count();

    if ($total === 0) return false;

    $rows = $orm->select(static::$_table . '.id')
      ->offset(null)
      ->limit(null)
      ->findArray();

    $ids = array_map(function($model) {
      return (int)$model['id'];
    }, $rows);

    if (is_callable($callback)) {
      $callback($ids);
    }

    // get number of affected rows
    return $orm->get_last_statement()
      ->rowCount();
  }

  public function duplicate($data = []) {
    $model = get_called_class();
    $modelData = array_merge($this->asArray(), $data);
    unset($modelData['id']);

    $duplicate = $model::create();
    $duplicate->hydrate($modelData);
    $duplicate->set_expr('created_at', 'NOW()');
    $duplicate->set_expr('updated_at', 'NOW()');
    if (isset($modelData['deleted_at'])) {
      $duplicate->set_expr('deleted_at', 'NULL');
    }

    $duplicate->save();
    return $duplicate;
  }

  public function setTimestamp() {
    if ($this->createdAt === null) {
      $this->set_expr('created_at', 'NOW()');
    }
  }

  public static function getPublished() {
    return static::whereNull('deleted_at');
  }

  public static function getTrashed() {
    return static::whereNotNull('deleted_at');
  }

  /**
   * Rethrow PDOExceptions to prevent exposing sensitive data in stack traces
   */
  public static function __callStatic($method, $parameters) {
    try {
      return parent::__callStatic($method, $parameters);
    } catch (\PDOException $e) {
      throw new \Exception($e->getMessage());
    }
  }

  public function validate() {
    $success = true;
    foreach (array_keys($this->_validations) as $field) {
      $success = $success && $this->validateField($field, $this->$field);
    }
    $this->setError($this->getValidationErrors());
    return $success;
  }

  public function __get($name) {
    $value = parent::__get($name);
    if ($value !== null) {
      return $value;
    }
    $name = Helpers::camelCaseToUnderscore($name);
    return parent::__get($name);
  }

  public function __set($name, $value) {
    $name = Helpers::camelCaseToUnderscore($name);
    return parent::__set($name, $value);
  }

  public function __isset($name) {
    $name = Helpers::camelCaseToUnderscore($name);
    return parent::__isset($name);
  }
}
