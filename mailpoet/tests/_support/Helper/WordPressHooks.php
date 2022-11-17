<?php declare(strict_types = 1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class WordPressHooks extends \Codeception\Module {

  private static $filtersApplied = [];
  private static $filtersAdded = [];
  private static $actionsDone = [];
  private static $actionsAdded = [];

  public static function interceptApplyFilters() {
    WordPress::interceptFunction('apply_filters', [__CLASS__, 'applyFilters']);
  }

  public static function interceptAddFilter() {
    WordPress::interceptFunction('add_filter', [__CLASS__, 'addFilter']);
  }

  public static function interceptDoAction() {
    WordPress::interceptFunction('do_action', [__CLASS__, 'doAction']);
  }

  public static function interceptAddAction() {
    WordPress::interceptFunction('add_action', [__CLASS__, 'addAction']);
  }

  public static function applyFilters() {
    $args = func_get_args();
    $hookName = array_shift($args);
    self::$filtersApplied[$hookName] = $args;
    return func_get_arg(1);
  }

  public static function addFilter() {
    $args = func_get_args();
    $hookName = array_shift($args);
    self::$filtersAdded[$hookName] = $args;
  }

  public static function doAction() {
    $args = func_get_args();
    $hookName = array_shift($args);
    self::$actionsDone[$hookName] = $args;
  }

  public static function addAction() {
    $args = func_get_args();
    $hookName = array_shift($args);
    self::$actionsAdded[$hookName] = $args;
  }

  public static function isFilterApplied($hookName) {
    return isset(self::$filtersApplied[$hookName]);
  }

  public static function isFilterAdded($hookName) {
    return isset(self::$filtersAdded[$hookName]);
  }

  public static function isActionDone($hookName) {
    return isset(self::$actionsDone[$hookName]);
  }

  public static function isActionAdded($hookName) {
    return isset(self::$actionsAdded[$hookName]);
  }

  public static function getFilterApplied($hookName) {
    return self::isFilterApplied($hookName) ? self::$filtersApplied[$hookName] : null;
  }

  public static function getFilterAdded($hookName) {
    return self::isFilterAdded($hookName) ? self::$filtersAdded[$hookName] : null;
  }

  public static function getActionDone($hookName) {
    return self::isActionDone($hookName) ? self::$actionsDone[$hookName] : null;
  }

  public static function getActionAdded($hookName) {
    return self::isActionAdded($hookName) ? self::$actionsAdded[$hookName] : null;
  }

  public static function releaseAllHooks() {
    WordPress::releaseAllFunctions();
    self::$filtersApplied = [];
    self::$filtersAdded = [];
    self::$actionsDone = [];
    self::$actionsAdded = [];
  }
}
