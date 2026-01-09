<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Integration\Trigger;
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

abstract class AbstractAutomationEmbed {
  protected AssetsController $assetsController;
  protected Registry $registry;
  protected Renderer $renderer;
  protected TrackingConfig $trackingConfig;
  protected WPFunctions $wp;
  protected SubscribersFeature $subscribersFeature;
  protected CapabilitiesManager $capabilitiesManager;
  protected ServicesChecker $servicesChecker;
  protected WooCommerceHelper $wooCommerceHelper;
  protected WooCommerceSubscriptionsHelper $wooCommerceSubscriptionsHelper;
  protected WooCommerceBookingsHelper $wooCommerceBookingsHelper;
  protected SubjectTransformerHandler $subjectTransformerHandler;

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
    SubjectTransformerHandler $subjectTransformerHandler
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
    $this->subjectTransformerHandler = $subjectTransformerHandler;
  }

  abstract public function render(): void;

  abstract protected function setupDependencies(): void;

  abstract protected function getTemplateName(): string;

  abstract protected function getCustomData(): array;

  protected function renderEmbed(): void {
    $this->disableAdminBarAndEmojis();
    $this->setupDependencies();

    $headContent = $this->captureHead();
    $footerContent = $this->captureFooter();

    $data = array_merge(
      $this->getBaseData(),
      $this->getCustomData(),
      [
        'head_content' => $headContent,
        'footer_content' => $footerContent,
      ]
    );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->renderer->render($this->getTemplateName(), $data);
    exit;
  }

  protected function disableAdminBarAndEmojis(): void {
    // Disable admin bar for embed (intentional for iframe display)
    // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
    add_filter('show_admin_bar', '__return_false');

    // Disable WordPress emoji handling (prevents deprecation warning)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
  }

  protected function captureHead(): string {
    ob_start();
    wp_head();
    return (string)ob_get_clean();
  }

  protected function captureFooter(): string {
    ob_start();
    wp_footer();
    return (string)ob_get_clean();
  }

  protected function getBaseData(): array {
    return [
      'locale' => $this->wp->getLocale(),
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
    ];
  }

  protected function buildRegistry(): array {
    $steps = [];
    foreach ($this->registry->getSteps() as $key => $step) {
      $steps[$key] = [
        'key' => $step->getKey(),
        'name' => $step->getName(),
        'subject_keys' => $step instanceof Trigger ? $this->subjectTransformerHandler->getSubjectKeysForTrigger($step) : $step->getSubjectKeys(),
        'args_schema' => $step->getArgsSchema()->toArray(),
      ];
    }

    $subjects = [];
    foreach ($this->registry->getSubjects() as $key => $subject) {
      $subjectFields = $subject->getFields();
      usort($subjectFields, function (Field $a, Field $b) {
        return $a->getName() <=> $b->getName();
      });

      $subjects[$key] = [
        'key' => $subject->getKey(),
        'name' => $subject->getName(),
        'args_schema' => $subject->getArgsSchema()->toArray(),
        'field_keys' => array_map(function ($field) {
          return $field->getKey();
        }, $subjectFields),
      ];
    }

    $fields = [];
    foreach ($this->registry->getFields() as $key => $field) {
      $fields[$key] = [
        'key' => $field->getKey(),
        'type' => $field->getType(),
        'name' => $field->getName(),
        'args' => $field->getArgs(),
      ];
    }

    $filters = [];
    foreach ($this->registry->getFilters() as $fieldType => $filter) {
      $conditions = [];
      foreach ($filter->getConditions() as $key => $label) {
        $conditions[] = [
          'key' => $key,
          'label' => $label,
        ];
      }
      $filters[$fieldType] = [
        'field_type' => $filter->getFieldType(),
        'conditions' => $conditions,
      ];
    }

    return [
      'steps' => $steps,
      'subjects' => $subjects,
      'fields' => $fields,
      'filters' => $filters,
    ];
  }

  protected function buildContext(): array {
    $data = [];
    foreach ($this->registry->getContextFactories() as $key => $factory) {
      $data[$key] = $factory();
    }
    return $data;
  }
}
