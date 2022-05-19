<?php

namespace MailPoet\Twig;

use MailPoet\Analytics\Analytics as AnalyticsGenerator;
use MailPoet\DI\ContainerWrapper;
use MailPoet\InvalidStateException;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

class Analytics extends AbstractExtension {

  /** @var  AnalyticsGenerator */
  private $analytics;

  public function getFunctions() {
    return [
      new TwigFunction(
        'get_analytics_data',
        [$this, 'generateAnalytics'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'is_analytics_enabled',
        [$this, 'isEnabled'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'get_analytics_public_id',
        [$this, 'getPublicId'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'is_analytics_public_id_new',
        [$this, 'isPublicIdNew'],
        ['is_safe' => ['all']]
      ),
    ];
  }

  private function analytics() {

    if ($this->analytics === null) {
      $this->analytics = ContainerWrapper::getInstance()->get(AnalyticsGenerator::class);
    }
    if (!$this->analytics instanceof AnalyticsGenerator) {
      throw new InvalidStateException('AnalyticsGenerator service was not registered!');
    }
    return $this->analytics;
  }

  public function generateAnalytics() {
    return $this->analytics()->generateAnalytics();
  }

  public function isEnabled() {
    return $this->analytics()->isEnabled();
  }

  public function getPublicId() {
    return $this->analytics()->getPublicId();
  }

  public function isPublicIdNew() {
    return $this->analytics()->isPublicIdNew();
  }
}
