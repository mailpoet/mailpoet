<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;
  const STATE_SUBSCRIBED = 1;
  const STATE_UNCONFIRMED = 0;
  const STATE_UNSUBSCRIBED = -1;

  function __construct() {
    parent::__construct();

    $this->addValidations('email', array(
      'required' => __('You need to enter your email address.'),
      'isEmail' => __('Your email address is invalid.')
    ));
  }
}
