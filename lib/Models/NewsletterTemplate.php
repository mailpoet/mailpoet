<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterTemplate extends Model {
  public static $_table = MP_NEWSLETTER_TEMPLATES_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
    $this->addValidations('body', array(
      'required' => __('The template body cannot be empty.', 'mailpoet')
    ));
  }

  function asArray() {
    $template = parent::asArray();
    if(isset($template['body'])) {
      $template['body'] = json_decode($template['body'], true);
    }
    return $template;
  }

}
