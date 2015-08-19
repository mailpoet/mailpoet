<?php
namespace MailPoet\Router;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }
  function set(array $data) {
    $subscriber = Subscriber::where('email', $data['email'])
      ->findOne();
    $to_create = ($subscriber === FALSE);
    if ($to_create === TRUE) {
      $subscriber = Subscriber::create();
    }
    $subscriber->hydrate($data);
    $subscriber->save();
    return $to_create;
  }
  function get() {
    $subscribers = \ORM::for_table(Subscriber::$_table)
      ->select(Subscriber::$_table.'.*')
      ->find_many();
    return $subscribers;
  }
}
