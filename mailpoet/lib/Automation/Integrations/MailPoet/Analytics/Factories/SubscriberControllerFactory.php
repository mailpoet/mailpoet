<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Factories;

use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\FreeSubscriberController;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\SubscriberController;
use MailPoet\Premium\Automation\Integrations\MailPoetPremium\Analytics\Controller\PremiumSubscriberController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SubscriberControllerFactory {


  /** @var SubscribersFeature */
  private $subscribersFeature;

  public function __construct(
    SubscribersFeature $subscribersFeature
  ) {
    $this->subscribersFeature = $subscribersFeature;
  }

  public function getSubscriberController(): SubscriberController {
    $container = \MailPoet\DI\ContainerWrapper::getInstance();
    return $this->isPremium() ?
      $container->get(PremiumSubscriberController::class) : /* @phpstan-ignore-line */
      $container->get(FreeSubscriberController::class);
  }

  private function isPremium(): bool {
    return
      class_exists(PremiumSubscriberController::class)
      && $this->subscribersFeature->hasValidApiKey()
      && !$this->subscribersFeature->check();
  }
}
