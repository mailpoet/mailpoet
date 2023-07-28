<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;

class FreeSubscriberController implements SubscriberController {


  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WordPress */
  private $wp;

  /** @var Registry */
  private $registry;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    WordPress $wp,
    Registry $registry
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->wp = $wp;
    $this->registry = $registry;
  }

  public function getSubscribersForAutomation(Automation $automation, Query $query): array {
    $items = [
      $this->addItem($automation, $query),
      $this->addItem($automation, $query),
      $this->addItem($automation, $query),
      $this->addItem($automation, $query),
    ];
    return [
      'results' => count($items),
      'items' => $items,
    ];
  }

  private function addItem(Automation $automation, Query $query): array {

    $subscriber = $this->getRandomSubscriber();

    return [
      'date' => $this->findRandomDateBetween($query->getAfter(), $query->getBefore())->format(\DateTimeImmutable::W3C),
      'subscriber' => [
        'id' => $subscriber->getId(),
        'email' => $subscriber->getEmail(),
        'first_name' => $subscriber->getFirstName(),
        'last_name' => $subscriber->getLastName(),
        'avatar' => $this->wp->getAvatarUrl($subscriber->getEmail(), ['size' => 40]),
      ],
      'run' => [
        'automation_id' => $automation->getId(),
        'status' => $this->getRandomStatus(),
        'step' => $this->getRandomStep($automation),
      ],
    ];
  }

  private function getRandomStep(Automation $automation): array {
    /** @var Step[] $steps */
    $steps = array_values(
      array_filter(
        $automation->getSteps(),
        function (Step $step) {
          return $step->getType() === Step::TYPE_ACTION;
        }
      )
    );
    if (!$steps) {
      return ['id' => '', 'name' => ''];
    }
    $step = $steps[array_rand($steps)];

    $stepType = $this->registry->getStep($step->getKey());
    return [
      'id' => $step->getId(),
      'name' => $stepType ? $stepType->getName() : __('Unknown', 'mailpoet'),
    ];
  }

  private function getRandomStatus(): string {
    $statuses = [AutomationRun::STATUS_RUNNING, AutomationRun::STATUS_COMPLETE];
    return $statuses[array_rand($statuses)];
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
}
