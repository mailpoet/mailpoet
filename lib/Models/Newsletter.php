<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('subject', array(
      'required' => __('You need to specify a subject.')
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

  static function createOrUpdate($data = array()) {
    $newsletter = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $newsletter = self::findOne((int)$data['id']);
    }

    if($newsletter === false) {
      $newsletter = self::create();
      $newsletter->hydrate($data);
    } else {
      unset($data['id']);
      $newsletter->set($data);
    }

    $saved = $newsletter->save();

    if($saved === false) {
      return $newsletter->getValidationErrors();
    } else {
      return true;
    }
  }
}
