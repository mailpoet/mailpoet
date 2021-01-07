<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Shortcodes\Shortcodes as NewsletterShortcodes;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use stdClass;

class Shortcodes {
  /**
   * @param string $content
   * @param string|null $contentSource
   * @param stdClass|Newsletter|null $newsletter
   * @param Subscriber|null $subscriber
   * @param Sending|SendingQueue|null $queue
   */
  public static function process($content, $contentSource = null, $newsletter = null, $subscriber = null, $queue = null) {
    /** @var NewsletterShortcodes $shortcodes */
    $shortcodes = ContainerWrapper::getInstance()->get(NewsletterShortcodes::class);
    /** @var SendingQueuesRepository $sendingQueueRepository */
    $sendingQueueRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
    /** @var NewslettersRepository $newsletterRepository */
    $newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    /** @var NewslettersRepository $newsletterRepository */
    $subscribersRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    /** @var SubscribersRepository $subscribersRepository */
    $subscribersRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);

    if (($queue instanceof Sending || $queue instanceof SendingQueue) && $queue->id) {
      $queue = $sendingQueueRepository->findOneById($queue->id);
    }
    if ($queue instanceof SendingQueueEntity) {
      $shortcodes->setQueue($queue);
    } else {
      $shortcodes->setQueue(null);
    }
    if (($newsletter instanceof Newsletter || $newsletter instanceof stdClass) && $newsletter->id) {
      $newsletter = $newsletterRepository->findOneById($newsletter->id);
    }
    if ($newsletter instanceof NewsletterEntity) {
      $shortcodes->setNewsletter($newsletter);
    } else {
      $shortcodes->setNewsletter(null);
    }
    if ($subscriber instanceof Subscriber && $subscriber->id) {
      $subscriber = $subscribersRepository->findOneById($subscriber->id);
    }
    if ($subscriber instanceof SubscriberEntity) {
      $shortcodes->setSubscriber($subscriber);
    } else {
      $shortcodes->setSubscriber(null);
    }
    return $shortcodes->replace($content, $contentSource);
  }
}
