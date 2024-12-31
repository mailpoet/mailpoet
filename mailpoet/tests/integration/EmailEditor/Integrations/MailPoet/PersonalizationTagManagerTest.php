<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;

class PersonalizationTagManagerTest extends \MailPoetTest {
  private NewsletterTask $newsletterTask;
  private NewsletterEntity $newsletter;
  private SendingQueueEntity $sendingQueueEntity;
  private ScheduledTaskEntity $scheduledTaskEntity;

  public function _before() {
    parent::_before();
    $this->newsletterTask = new NewsletterTask();
    $this->newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->create();

    $this->scheduledTaskEntity = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->sendingQueueEntity = (new SendingQueueFactory())->create($this->scheduledTaskEntity, $this->newsletter);
  }

  public function testItHooksToPostRenderToReplaceLinksInHrefByShortcodes() {
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    // @phpstan-ignore-next-line The structure is hardcoded in the fixture
    $body['content']['blocks'][0]['blocks'][0]['blocks'][0]['text'] = '
        <a data-link-href="[mailpoet/subscription-unsubscribe-url]">Unsubscribe</a>
        <a data-link-href="[mailpoet/subscription-manage-url]">Manage</a>
        <a data-link-href="[mailpoet/newsletter-view-in-browser-url]">View in browser</a>
        <!--[mailpoet/subscription-unsubscribe-url]-->
      ';
    $this->newsletter->setBody((array)$body);
    $personalizationManager = $this->diContainer->get(PersonalizationTagManager::class);
    $personalizationManager->initialize();

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);

    $newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);

    /** @var array{html: string, text: string}  $rendered */
    $rendered = $this->sendingQueueEntity->getNewsletterRenderedBody();

    // Ensure link were properly extracted and replaced in email body
    $unsubscribeLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[link:subscription_unsubscribe_url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $unsubscribeLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $unsubscribeLink->getHash() . '">Unsubscribe</a>', $rendered['html']);

    $manageLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[link:subscription_manage_url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $manageLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $manageLink->getHash() . '">Manage</a>', $rendered['html']);

    $viewInBrowserLink = $newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter, 'queue' => $this->sendingQueueEntity, 'url' => '[link:newsletter_view_in_browser_url]']);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $viewInBrowserLink);
    $this->assertStringContainsString('<a href="[mailpoet_click_data]-' . $viewInBrowserLink->getHash() . '">View in browser</a>', $rendered['html']);

    // Tag placed out of href was not replaced
    $this->assertStringContainsString('<!--[mailpoet/subscription-unsubscribe-url]-->', $rendered['html']);
  }
}
