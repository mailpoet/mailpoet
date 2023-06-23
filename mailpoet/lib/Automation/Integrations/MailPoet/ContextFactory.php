<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Segments\SegmentsRepository;

class ContextFactory {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
  }

  /** @return mixed[] */
  public function getContextData(): array {
    return [
      'segments' => $this->getSegments(),
      'userRoles' => $this->getUserRoles(),
    ];
  }

  private function getSegments(): array {
    $segments = [];
    foreach ($this->segmentsRepository->findAll() as $segment) {
      $segments[] = [
        'id' => $segment->getId(),
        'name' => $segment->getName(),
        'type' => $segment->getType(),
      ];
    }
    return $segments;
  }

  private function getUserRoles(): array {
    $userRoles = [];
    foreach (wp_roles()->roles as $role => $details) {
      $userRoles[] = [
        'id' => $role,
        'name' => $details['name'],
      ];
    }
    return $userRoles;
  }
}
