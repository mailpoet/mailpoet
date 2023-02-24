<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Segments\SegmentsRepository;

class ContextFactory {
  /** @var DynamicSegmentsContextFactory */
  private $dynamicSegmentsContextFactory;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    DynamicSegmentsContextFactory $dynamicSegmentsContextFactory,
    SegmentsRepository $segmentsRepository
  ) {
    $this->dynamicSegmentsContextFactory = $dynamicSegmentsContextFactory;
    $this->segmentsRepository = $segmentsRepository;
  }

  /** @return mixed[] */
  public function getContextData(): array {
    return [
      'segments' => $this->getSegments(),
      'dynamic_segments' => $this->dynamicSegmentsContextFactory->getDynamicSegmentContext()
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
}
