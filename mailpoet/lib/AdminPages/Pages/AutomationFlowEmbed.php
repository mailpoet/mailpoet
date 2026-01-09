<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Mappers\AutomationMapper;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\WooCommerceBookings\Helper as WooCommerceBookingsHelper;
use MailPoet\WooCommerce\WooCommerceSubscriptions\Helper as WooCommerceSubscriptionsHelper;
use MailPoet\WP\Functions as WPFunctions;

class AutomationFlowEmbed extends AbstractAutomationEmbed {
  private AutomationStorage $automationStorage;
  private AutomationMapper $automationMapper;

  public function __construct(
    AssetsController $assetsController,
    Registry $registry,
    Renderer $renderer,
    TrackingConfig $trackingConfig,
    WPFunctions $wp,
    SubscribersFeature $subscribersFeature,
    CapabilitiesManager $capabilitiesManager,
    ServicesChecker $servicesChecker,
    WooCommerceHelper $wooCommerceHelper,
    WooCommerceSubscriptionsHelper $wooCommerceSubscriptionsHelper,
    WooCommerceBookingsHelper $wooCommerceBookingsHelper,
    SubjectTransformerHandler $subjectTransformerHandler,
    AutomationStorage $automationStorage,
    AutomationMapper $automationMapper
  ) {
    parent::__construct(
      $assetsController,
      $registry,
      $renderer,
      $trackingConfig,
      $wp,
      $subscribersFeature,
      $capabilitiesManager,
      $servicesChecker,
      $wooCommerceHelper,
      $wooCommerceSubscriptionsHelper,
      $wooCommerceBookingsHelper,
      $subjectTransformerHandler
    );
    $this->automationStorage = $automationStorage;
    $this->automationMapper = $automationMapper;
  }

  public function render(): void {
    $this->renderEmbed();
  }

  protected function setupDependencies(): void {
    $this->assetsController->setupAutomationFlowEmbedDependencies();
  }

  protected function getTemplateName(): string {
    return 'automation/flow-embed.html';
  }

  protected function getCustomData(): array {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    $automation = $id ? $this->automationStorage->getAutomation($id) : null;
    $automationData = $automation ? $this->automationMapper->buildAutomation($automation) : null;

    return [
      'automation_id' => $id,
      'automation' => $automationData,
    ];
  }
}
