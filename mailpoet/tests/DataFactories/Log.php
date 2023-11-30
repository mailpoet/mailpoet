<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\LogEntity;
use MailPoet\Logging\LogRepository;
use MailPoetVendor\Carbon\Carbon;

class Log {
  /** @var array */
  private $data;

  /** @var LogRepository */
  private $repository;

  public function __construct() {
    $this->repository = ContainerWrapper::getInstance()->get(LogRepository::class);
    $this->data = [
      'name' => 'Log' . bin2hex(random_bytes(7)),
      'level' => 5,
      'message' => 'Message' . bin2hex(random_bytes(7)),
      'created_at' => Carbon::now(),
    ];
  }

  /**
   * @return $this
   */
  public function withCreatedAt(\DateTimeInterface $date): Log {
    return $this->update('created_at', $date);
  }

  public function create(): LogEntity {
    $entity = new LogEntity();
    $entity->setName($this->data['name']);
    $entity->setLevel($this->data['level']);
    $entity->setMessage($this->data['message']);
    $entity->setCreatedAt($this->data['created_at']);
    $this->repository->saveLog($entity);
    return $entity;
  }

  /**
   * @return $this
   */
  private function update(string $item, $value): Log {
    $data = $this->data;
    $data[$item] = $value;
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
