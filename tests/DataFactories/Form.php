<?php

namespace MailPoet\Test\DataFactories;

use Carbon\Carbon;
use MailPoet\Config\Database;

require_once __DIR__ . '/../../lib/Config/Database.php';

class Form {

  private $data;

  public function __construct() {
    $this->data = [
      'name' => 'New form',
      'body' => 'a:2:{i:0;a:5:{s:2:"id";s:5:"email";s:4:"name";s:5:"Email";s:4:"type";s:4:"text";s:6:"static";b:1;s:6:"params";a:2:{s:5:"label";s:5:"Email";s:8:"required";b:1;}}i:1;a:5:{s:2:"id";s:6:"submit";s:4:"name";s:6:"Submit";s:4:"type";s:6:"submit";s:6:"static";b:1;s:6:"params";a:1:{s:5:"label";s:10:"Subscribe!";}}}',
      'settings' => 'a:4:{s:10:"on_success";s:7:"message";s:15:"success_message";s:61:"Check your inbox or spam folder to confirm your subscription.";s:8:"segments";N;s:20:"segments_selected_by";s:5:"admin";}',
      'created_at' => Carbon::now(),
      'updated_at' => Carbon::now(),
    ];
  }

  public function withName($name) {
    $this->data['name'] = $name;
    return $this;
  }

  public function withDeleted() {
    $this->data['deleted_at'] = Carbon::now();
    return $this;
  }

  public function create() {
    if(!defined('MP_FORMS_TABLE')) {
      $database = new Database();
      $database->defineTables();
    }
    \MailPoet\Models\Form::createOrUpdate($this->data);
  }

}
