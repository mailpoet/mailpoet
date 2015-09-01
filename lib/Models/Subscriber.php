<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('email', array(
      'required' => __('You need to enter your email address.'),
      'isEmail' => __('Your email address is invalid.')
    ));
  }

  public function lists() {
    return self::has_many_through(__NAMESPACE__ . '\SubscriberList', __NAMESPACE__ . '\RelationSubscriberList', 'subscriber_id', 'list_id');
  }
}
