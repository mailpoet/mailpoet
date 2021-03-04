<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\Form as FormModel;
use MailPoetVendor\Carbon\Carbon;

class Form {

  private $data;

  public function __construct() {
    $this->data = [
      'name' => 'New form',
      'body' => 'a:2:{i:0;a:5:{s:2:"id";s:5:"email";s:4:"name";s:5:"Email";s:4:"type";s:4:"text";s:6:"static";b:1;s:6:"params";a:2:{s:5:"label";s:5:"Email";s:8:"required";b:1;}}i:1;a:5:{s:2:"id";s:6:"submit";s:4:"name";s:6:"Submit";s:4:"type";s:6:"submit";s:6:"static";b:1;s:6:"params";a:1:{s:5:"label";s:10:"Subscribe!";}}}',
      'settings' => [
        'on_success' => 'message',
        'success_message' => FormModel::getDefaultSuccessMessage(),
        'segments' => [2],
        'segments_selected_by' => 'admin',
      ],
      'status' => 'enabled',
      'created_at' => Carbon::now(),
      'updated_at' => Carbon::now(),
    ];
  }

  /**
   * @param string $name
   * @return $this
   */
  public function withName($name) {
    $this->data['name'] = $name;
    return $this;
  }

  /**
   * @return $this
   */
  public function withFirstName() {
    return $this->addFormBlock([
      'type' => 'text',
      'params' => [
        'label' => 'First name',
        'class_name' => '',
        'label_within' => '1',
      ],
      'id' => 'first_name',
      'name' => 'First name',
      'styles' => [
        'full_width' => '0',
      ],
    ]);
  }

  /**
   * @return $this
   */
  public function withLastName() {
    return $this->addFormBlock([
      'type' => 'text',
      'params' => [
        'label' => 'Last name',
        'class_name' => '',
        'label_within' => '1',
      ],
      'id' => 'last_name',
      'name' => 'Last name',
      'styles' => [
        'full_width' => '0',
      ],
    ]);
  }

  /**
   * @param array $block
   * @return $this
   */
  private function addFormBlock(array $block) {
    $body = unserialize($this->data['body']);
    $body = array_merge([$block], $body);
    $this->data['body'] = serialize($body);
    return $this;
  }

  /**
   * @return $this
   */
  public function withDeleted() {
    $this->data['deleted_at'] = Carbon::now();
    return $this;
  }

  /**
   * @param SegmentEntity[] $segments
   * @return $this
   */
  public function withSegments(array $segments) {
    $ids = [];
    foreach ($segments as $segment) {
      $ids[] = $segment->getId();
    }
    $this->data['settings']['segments'] = $ids;
    return $this;
  }

  /**
   * @return $this
   */
  public function withDisplayBelowPosts() {
    $this->data['settings']['form_placement'] = [
      'below_posts' => [
        'enabled' => '1',
        'pages' => ['all' => ''],
        'posts' => ['all' => '1'],
      ],
    ];
    return $this;
  }

  /** @return \MailPoet\Models\Form */
  public function create() {
    return FormModel::createOrUpdate($this->data);
  }

  public function withDefaultSuccessMessage() {
    FormModel::updateSuccessMessages();
  }

  /**
   * @return $this
   */
  public function withSuccessMessage(string $message) {
    $this->data['settings']['on_success'] = 'message';
    $this->data['settings']['success_message'] = $message;
    return $this;
  }
}
