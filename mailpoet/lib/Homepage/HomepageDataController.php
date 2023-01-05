<?php declare(strict_types = 1);

namespace MailPoet\Homepage;

use MailPoet\Form\FormsRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class HomepageDataController {
  /** @var SettingsController */
  private $settingsController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  public function __construct(
    SettingsController $settingsController,
    SubscribersRepository $subscribersRepository,
    FormsRepository $formsRepository,
    WooCommerceHelper $wooCommerceHelper
  ) {
    $this->settingsController = $settingsController;
    $this->subscribersRepository = $subscribersRepository;
    $this->formsRepository = $formsRepository;
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  public function getPageData(): array {
    $subscribersCount = $this->subscribersRepository->getTotalSubscribers();
    $formsCount = $this->formsRepository->count();
    return [
      'task_list_dismissed' => (bool)$this->settingsController->get('homepage.task_list_dismissed', false),
      'product_discovery_dismissed' => (bool)$this->settingsController->get('homepage.product_discovery_dismissed', false),
      'task_list_status' => $this->getTaskListStatus($subscribersCount, $formsCount),
      'woo_customers_count' => $this->wooCommerceHelper->getCustomersCount(),
      'subscribers_count' => $subscribersCount,
    ];
  }

  /**
   * @return array{senderSet:bool, mssConnected:bool, wooSubscribersImported:bool, subscribersAdded:bool}
   */
  private function getTaskListStatus(int $subscribersCount, int $formsCount): array {
    return [
      'senderSet' => (bool)$this->settingsController->get('sender.address', false),
      'mssConnected' => Bridge::isMSSKeySpecified(),
      'wooSubscribersImported' => (bool)$this->settingsController->get('woocommerce_import_screen_displayed', false),
      'subscribersAdded' => $formsCount || ($subscribersCount > 10),
    ];
  }
}
