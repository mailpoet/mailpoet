<?php
namespace Helper;
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class WordPressHooks extends \Codeception\Module
{
  private static $filters_applied = array();
  private static $filters_added = array();
  private static $actions_done = array();
  private static $actions_added = array();

  static function interceptApplyFilters() {
    WordPress::interceptFunction('apply_filters', array(__CLASS__, 'applyFilters'));
  }

  static function interceptAddFilter() {
    WordPress::interceptFunction('add_filter', array(__CLASS__, 'addFilter'));
  }

  static function interceptDoAction() {
    WordPress::interceptFunction('do_action', array(__CLASS__, 'doAction'));
  }

  static function interceptAddAction() {
    WordPress::interceptFunction('add_action', array(__CLASS__, 'addAction'));
  }

  static function applyFilters() {
    $args = func_get_args();
    $hook_name = array_shift($args);
    self::$filters_applied[$hook_name] = $args;
    return func_get_arg(1);
  }

  static function addFilter() {
    $args = func_get_args();
    $hook_name = array_shift($args);
    self::$filters_added[$hook_name] = $args;
  }

  static function doAction() {
    $args = func_get_args();
    $hook_name = array_shift($args);
    self::$actions_done[$hook_name] = $args;
  }

  static function addAction() {
    $args = func_get_args();
    $hook_name = array_shift($args);
    self::$actions_added[$hook_name] = $args;
  }

  static function isFilterApplied($hook_name) {
    return isset(self::$filters_applied[$hook_name]);
  }

  static function isFilterAdded($hook_name) {
    return isset(self::$filters_added[$hook_name]);
  }

  static function isActionDone($hook_name) {
    return isset(self::$actions_done[$hook_name]);
  }

  static function isActionAdded($hook_name) {
    return isset(self::$actions_added[$hook_name]);
  }

  static function getFilterApplied($hook_name) {
    return self::isFilterApplied($hook_name) ? self::$filters_applied[$hook_name] : null;
  }

  static function getFilterAdded($hook_name) {
    return self::isFilterAdded($hook_name) ? self::$filters_added[$hook_name] : null;
  }

  static function getActionDone($hook_name) {
    return self::isActionDone($hook_name) ? self::$actions_done[$hook_name] : null;
  }

  static function getActionAdded($hook_name) {
    return self::isActionAdded($hook_name) ? self::$actions_added[$hook_name] : null;
  }

  static function releaseAllHooks() {
    WordPress::releaseAllFunctions();
    self::$filters_applied = array();
    self::$filters_added = array();
    self::$actions_done = array();
    self::$actions_added = array();
  }
}
