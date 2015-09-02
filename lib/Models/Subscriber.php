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

  static function search($orm, $search = '') {
    return $orm->where_raw(
      '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
      array('%'.$search.'%', '%'.$search.'%', '%'.$search.'%')
    );
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Subscriber::count()
      ),
      array(
        'name' => 'subscribed',
        'label' => __('Subscribed'),
        'count' => Subscriber::where(
            'status',
            Subscriber::STATE_SUBSCRIBED
          )->count()
      ),
      array(
        'name' => 'unconfirmed',
        'label' => __('Unconfirmed'),
        'count' => Subscriber::where(
            'status',
            Subscriber::STATE_UNCONFIRMED
          )->count()
      ),
      array(
        'name' => 'unsubscribed',
        'label' => __('Unsubscribed'),
        'count' => Subscriber::where(
            'status',
            Subscriber::STATE_UNSUBSCRIBED
          )->count()
      )
    );
  }

  static function group($orm, $group = null) {
    switch($group) {
      case 'subscribed':
        return $orm->where('status', Subscriber::STATE_SUBSCRIBED);
      break;

      case 'unconfirmed':
        return $orm->where('status', Subscriber::STATE_UNCONFIRMED);
      break;

      case 'unsubscribed':
        return $orm->where('status', Subscriber::STATE_UNSUBSCRIBED);
      break;
    }
  }
}
