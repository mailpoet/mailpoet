<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\FormsRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class Homepage {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settingsController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settingsController,
    SubscribersRepository $subscribersRepository,
    FormsRepository $formsRepository,
    WooCommerceHelper $wooCommerceHelper
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settingsController = $settingsController;
    $this->subscribersRepository = $subscribersRepository;
    $this->formsRepository = $formsRepository;
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  public function render() {
    $data = [
      'mta_log' => $this->settingsController->get('mta_log'),
      'homepage' => [
        'task_list_dismissed' => (bool)$this->settingsController->get('homepage.task_list_dismissed', false),
        'task_list_status' => $this->getTaskListStatus(),
        'woo_customers_count' => $this->wooCommerceHelper->getCustomersCount(),
      ],
    ];
    $this->pageRenderer->displayPage('homepage.html', $data);
  }

  /**
   * @return array{senderSet:bool, mssConnected:bool, wooSubscribersImported:bool, subscribersAdded:bool}
   */
  private function getTaskListStatus(): array {
    $subscribersCount = $this->subscribersRepository->getTotalSubscribers();
    $formsCount = $this->formsRepository->count();
    return [
      'senderSet' => (bool)$this->settingsController->get('sender.address', false),
      'mssConnected' => Bridge::isMSSKeySpecified(),
      'wooSubscribersImported' => (bool)$this->settingsController->get('woocommerce_import_screen_displayed', false),
      'subscribersAdded' => $formsCount || ($subscribersCount > 10),
    ];
  }
}
