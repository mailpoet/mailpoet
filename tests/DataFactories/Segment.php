<?php

namespace MailPoet\Test\DataFactories;

use MailPoetVendor\Carbon\Carbon;

class Segment {

  protected $data;

  public function __construct() {
    $this->data = [
      'name' => 'List ' . bin2hex(random_bytes(7)), // phpcs:ignore
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
   * @param string $description
   * @return $this
   */
  public function withDescription($description) {
    $this->data['description'] = $description;
    return $this;
  }

  /**
   * @return $this
   */
  public function withDeleted() {
    $this->data['deleted_at'] = Carbon::now();
    return $this;
  }

  /** @return \MailPoet\Models\Segment */
  public function create() {
    return \MailPoet\Models\Segment::createOrUpdate($this->data);
  }
}
