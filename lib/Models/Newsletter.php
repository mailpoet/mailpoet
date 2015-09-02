<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('subject', array(
      'required' => 'subject_is_blank',
      'isString' => 'subject_is_not_string'
    ));
    $this->addValidations('body', array(
      'required' => 'body_is_blank',
      'isString' => 'body_is_not_string'
    ));
  }

  static function search($orm, $search = '') {
    return $orm->where_like('subject', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Newsletter::count()
      )
    );
  }

  static function group($orm, $group = null) {
  }
}
