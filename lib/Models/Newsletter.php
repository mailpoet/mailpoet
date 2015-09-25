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
    $this->addValidations('body', array(
      'required' => __('Newsletter cannot be empty.')
    ));
  }

  static function search($orm, $search = '') {
    return $orm->where_like('subject', '%' . $search . '%');
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

    if(isset($data['id']) && (int) $data['id'] > 0) {
      $newsletter = self::findOne((int) $data['id']);
    }

    if($newsletter === false) {
      $newsletter = self::create();
      $newsletter->hydrate($data);
    } else {
      unset($data['id']);
      $newsletter->set($data);
    }

    $saved = $newsletter->save();

    if($saved === true) {
      return true;
    } else {
      $errors = $newsletter->getValidationErrors();
      if(!empty($errors)) {
        return $errors;
      }
    }
    return false;
  }

  static function trash($listing) {
    return $listing->getSelection()
      ->deleteMany();
  }
}
