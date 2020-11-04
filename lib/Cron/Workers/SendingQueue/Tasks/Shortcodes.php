<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Shortcodes\Shortcodes as NewsletterShortcodes;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;

class Shortcodes {
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

    if ($queue instanceof Sending || $queue instanceof SendingQueue) {
      $queue = $sendingQueueRepository->findOneById($queue->id);
    }
    if ($queue instanceof SendingQueueEntity) {
      $shortcodes->setQueue($queue);
    }
    if ($newsletter instanceof \MailPoet\Models\Newsletter) {
      $newsletter = $newsletterRepository->findOneById($newsletter->id);
    }
    if ($newsletter instanceof NewsletterEntity) {
      $shortcodes->setNewsletter($newsletter);
    }
    if ($subscriber instanceof Subscriber) {
      $subscriber = $subscribersRepository->findOneById($subscriber->id);
    }
    if ($subscriber instanceof SubscriberEntity) {
      $shortcodes->setSubscriber($subscriber);
    }
    return $shortcodes->replace($content, $contentSource);
  }
}
