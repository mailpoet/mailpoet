<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Subscribers\SubscribersRepository;

class FreeOrderController implements OrderController {

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WooCommerce */
  private $woocommerce;

  /** @var WordPress */
  private $wp;

  /** @var AutomationTimeSpanController */
  private $automationTimeSpanController;

  public function __construct(
    NewslettersRepository $newsletterRepository,
    SubscribersRepository $subscribersRepository,
    WooCommerce $woocommerce,
    WordPress $wp,
    AutomationTimeSpanController $automationTimeSpanController
  ) {
    $this->newslettersRepository = $newsletterRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->woocommerce = $woocommerce;
    $this->wp = $wp;
    $this->automationTimeSpanController = $automationTimeSpanController;
  }

  public function getOrdersForAutomation(Automation $automation, Query $query): array {
    $allEmails = $this->automationTimeSpanController->getAutomationEmailsInTimeSpan($automation, $query->getAfter(), $query->getBefore());
    $items = [
      $this->addItem($automation, $query),
      $this->addItem($automation, $query),
      $this->addItem($automation, $query),
      $this->addItem($automation, $query),
    ];
    return [
      'results' => count($items),
      'items' => $items,
      'emails' => array_map(
        function(NewsletterEntity $email): array {
          return [
            'id' => (string)$email->getId(),
            'name' => $email->getSubject(),
          ];
        },
        $allEmails
      ),
    ];
  }

  private function addItem(Automation $automation, Query $query): array {
    $newsletter = $this->getRandomEmailFromAutomation($automation);
    $currentOrder = $this->getRandomOrder();

    $subscriber = $this->getRandomSubscriber();
    $products = $currentOrder ? array_values(array_filter(array_map(
      function(\WC_Order_Item $lineItem): ?array {
        if (!$lineItem instanceof \WC_Order_Item_Product) {
          return null;
        }
        return [
          'id' => $lineItem->get_product_id(),
          'name' => $lineItem->get_name(),
          'quantity' => $lineItem->get_quantity(),
        ];
      },
      $currentOrder->get_items()
    ))) : [];

    return [
      'date' => $this->findRandomDateBetween($query->getAfter(), $query->getBefore())->format(\DateTimeImmutable::W3C),
      'customer' => [
        'id' => $subscriber->getId(),
        'email' => $subscriber->getEmail(),
        'first_name' => $subscriber->getFirstName(),
        'last_name' => $subscriber->getLastName(),
        'avatar' => $this->wp->getAvatarUrl($subscriber->getEmail(), ['size' => 20]),
      ],
      'details' => [
        'id' => $currentOrder ? $currentOrder->get_id() : null,
        'status' => $currentOrder ? [
          'id' => $currentOrder->get_status(),
          'name' => $this->woocommerce->wcGetOrderStatusName($currentOrder->get_status()),
          ] : null,
        'total' => $currentOrder ? (float)$currentOrder->get_total() : null,
        'products' => $products,
      ],
        'email' => [
        'subject' => $newsletter ? $newsletter->getSubject() : '',
        'id' => $newsletter ? $newsletter->getId() : 0,
      ],
    ];
  }

  private function findRandomDateBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): \DateTime {
    $start = new \DateTime($start->format(\DateTime::W3C));
    $start->setTimezone($this->wp->wpTimezone());
    $end = new \DateTime($end->format(\DateTime::W3C));
    $end->setTimezone($this->wp->wpTimezone());
    $randomTimestamp = mt_rand($start->getTimestamp(), $end->getTimestamp());
    $randomDate = new \DateTime();
    $randomDate->setTimestamp($randomTimestamp);
    $randomDate->setTimezone($this->wp->wpTimezone());
    return $randomDate;
  }

  private function getRandomSubscriber(): SubscriberEntity {
    $subscribers = $this->subscribersRepository->findBy([], null, 100);
    if (!$subscribers) {
      /** @var string $email */
      $email = $this->wp->getOption('admin_email');
      $subscriber = new SubscriberEntity();
      $subscriber->setFirstName('John');
      $subscriber->setLastName('Doe');
      $subscriber->setEmail($email);
      return $subscriber;
    }

    return $subscribers[array_rand($subscribers)];
  }

  private function getRandomOrder(): ?\WC_Order {
    $orders = $this->woocommerce->wcGetOrders([
      'limit' => 1,
      'type' => 'shop_order',
      'orderby' => 'rand',
    ]);
    return is_array($orders) && count($orders) ? current($orders) : null;
  }

  private function getRandomEmailFromAutomation(Automation $automation): ?NewsletterEntity {
    $emails = $this->getEmailsFromAutomation($automation);
    return count($emails) ? $emails[array_rand($emails)] : null;
  }

  /**
   * @param Automation $automation
   * @return NewsletterEntity[]
   */
  private function getEmailsFromAutomation(Automation $automation): array {
    $emails = [];
    foreach ($automation->getSteps() as $step) {
      if ($step->getKey() === 'mailpoet:send-email') {
        $emails[] = $this->newslettersRepository->findOneById($step->getArgs()['email_id']);
      }
    }
    return array_values(array_filter($emails));
  }
}
