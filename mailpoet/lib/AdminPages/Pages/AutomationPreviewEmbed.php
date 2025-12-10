<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\WooCommerceBookings\Helper as WooCommerceBookingsHelper;
use MailPoet\WooCommerce\WooCommerceSubscriptions\Helper as WooCommerceSubscriptionsHelper;
use MailPoet\WP\Functions as WPFunctions;

class AutomationPreviewEmbed {
  private AssetsController $assetsController;
  private Registry $registry;
  private Renderer $renderer;
  private TrackingConfig $trackingConfig;
  private WPFunctions $wp;
  private SubscribersFeature $subscribersFeature;
  private CapabilitiesManager $capabilitiesManager;
  private ServicesChecker $servicesChecker;
  private WooCommerceHelper $wooCommerceHelper;
  private WooCommerceSubscriptionsHelper $wooCommerceSubscriptionsHelper;
  private WooCommerceBookingsHelper $wooCommerceBookingsHelper;

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
    WooCommerceBookingsHelper $wooCommerceBookingsHelper
  ) {
    $this->assetsController = $assetsController;
    $this->registry = $registry;
    $this->renderer = $renderer;
    $this->trackingConfig = $trackingConfig;
    $this->wp = $wp;
    $this->subscribersFeature = $subscribersFeature;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->servicesChecker = $servicesChecker;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wooCommerceSubscriptionsHelper = $wooCommerceSubscriptionsHelper;
    $this->wooCommerceBookingsHelper = $wooCommerceBookingsHelper;
  }

  public function render(): void {
    $templateSlug = isset($_GET['template']) ? sanitize_key($_GET['template']) : '';

    // Disable admin bar for embed preview (intentional for iframe display)
    // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
    add_filter('show_admin_bar', '__return_false');

    // Disable WordPress emoji handling (prevents deprecation warning)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');

    // Load preview embed dependencies (lighter setup without full admin bundle)
    $this->assetsController->setupAutomationPreviewEmbedDependencies();

    ob_start();
    wp_head();
    $headContent = ob_get_clean();

    // Capture wp_footer() output
    ob_start();
    wp_footer();
    $footerContent = ob_get_clean();

    // Build template data
    $data = [
      'locale' => $this->wp->getLocale(),
      'template_slug' => $templateSlug,
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'registry' => $this->buildRegistry(),
      'context' => $this->buildContext(),
      'tracking_config' => $this->trackingConfig->getConfig(),
      'has_valid_premium_key' => $this->subscribersFeature->hasValidPremiumKey(),
      'subscribers_limit_reached' => $this->subscribersFeature->check(),
      'premium_active' => $this->servicesChecker->isPremiumPluginActive(),
      'capabilities' => $this->capabilitiesManager->getCapabilities(),
      'woocommerce_active' => $this->wooCommerceHelper->isWooCommerceActive(),
      'woocommerce_subscriptions_active' => $this->wooCommerceSubscriptionsHelper->isWooCommerceSubscriptionsActive(),
      'woocommerce_bookings_active' => $this->wooCommerceBookingsHelper->isWooCommerceBookingsActive(),
      'head_content' => $headContent,
      'footer_content' => $footerContent,
    ];

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->renderer->render('automation/preview-embed.html', $data);
    exit;
  }

  private function buildRegistry(): array {
    $steps = [];
    foreach ($this->registry->getSteps() as $key => $step) {
      $steps[$key] = [
        'key' => $step->getKey(),
        'name' => $step->getName(),
        'args_schema' => $step->getArgsSchema()->toArray(),
      ];
    }

    $subjects = [];
    foreach ($this->registry->getSubjects() as $key => $subject) {
      $subjects[$key] = [
        'key' => $subject->getKey(),
        'name' => $subject->getName(),
        'args_schema' => $subject->getArgsSchema()->toArray(),
        'field_keys' => array_map(function ($field) {
          return $field->getKey();
        }, $subject->getFields()),
      ];
    }

    return [
      'steps' => $steps,
      'subjects' => $subjects,
    ];
  }

  private function buildContext(): array {
    $data = [];
    foreach ($this->registry->getContextFactories() as $key => $factory) {
      $data[$key] = $factory();
    }
    return $data;
  }
}
