<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Factories;

use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\FreeOrderController;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\OrderController;
use MailPoet\Premium\Automation\Integrations\MailPoetPremium\Analytics\Controller\PremiumOrderController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class OrderControllerFactory {


  /** @var SubscribersFeature */
  private $subscribersFeature;

  public function __construct(
    SubscribersFeature $subscribersFeature
  ) {
    $this->subscribersFeature = $subscribersFeature;
  }

  public function getOrderController(): OrderController {
    $container = \MailPoet\DI\ContainerWrapper::getInstance();
    return $this->isPremium() ?
      $container->get(PremiumOrderController::class) : /* @phpstan-ignore-line */
      $container->get(FreeOrderController::class);
  }

  private function isPremium(): bool {
    return
      class_exists(PremiumOrderController::class)
      && $this->subscribersFeature->hasValidApiKey()
      && !$this->subscribersFeature->check();
  }
}
