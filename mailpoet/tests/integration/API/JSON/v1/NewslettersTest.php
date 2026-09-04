<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\API\JSON\v1\Newsletters;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WooCommerce\OrderAttributionRevenueReader;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewslettersTest extends \MailPoetTest {

  /** @var NewsletterEntity */
  public $postNotification;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var Newsletters */
  private $endpoint;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  public function _before() {
    parent::_before();
    $this->newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->newslettersResponseBuilder = ContainerWrapper::getInstance()->get(NewslettersResponseBuilder::class);
    $this->endpoint = Stub::copy(
      ContainerWrapper::getInstance()->get(Newsletters::class),
      [
        'newslettersResponseBuilder' => new NewslettersResponseBuilder(
          $this->diContainer->get(EntityManager::class),
          $this->diContainer->get(NewslettersRepository::class),
          new NewsletterStatisticsRepository(
            $this->diContainer->get(EntityManager::class),
            $this->makeEmpty(WCHelper::class),
            $this->diContainer->get(TrackingConfig::class),
            $this->diContainer->get(OrderAttributionRevenueReader::class),
            $this->diContainer->get(TrackingConsentController::class)
          ),
          $this->diContainer->get(Url::class),
          $this->diContainer->get(SendingQueuesRepository::class),
          $this->diContainer->get(LogRepository::class),
          $this->diContainer->get(ShareVisibility::class),
          $this->diContainer->get(StatisticsUnsubscribesRepository::class)
        ),
      ]
    );
    $this->newsletter = (new Newsletter())->withDefaultBody()->withSubject('My Standard Newsletter')->create();
    $this->postNotification = (new Newsletter())->withPostNotificationsType()->withSubject('My Post Notification')->loadBodyFrom('newsletterWithALC.json')->create();
  }

  public function testItCanGetANewsletter() {
    $response = $this->endpoint->get(); // missing id
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This email does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This email does not exist.');

    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $this->endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'wp' => $wp,
    ]);
    $response = $this->endpoint->get(['id' => $this->newsletter->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
      NewslettersResponseBuilder::RELATION_OPTIONS,
      NewslettersResponseBuilder::RELATION_QUEUE,
    ]));
    $hookName = 'mailpoet_api_newsletters_get_after';
    verify(WPHooksHelper::isFilterApplied($hookName))->true();
    verify(WPHooksHelper::getFilterApplied($hookName)[0])->isArray();
  }

  public function testItCanSaveANewsletter() {
    $newsletterData = [
      'id' => $this->newsletter->getId(),
      'type' => 'Updated type',
      'subject' => 'Updated subject',
      'preheader' => 'Updated preheader',
      'body' => '{"value": "Updated body"}',
      'sender_name' => 'Updated sender name',
      'sender_address' => 'Updated sender address',
      'reply_to_name' => 'Updated reply-to name',
      'reply_to_address' => 'Updated reply-to address',
      'ga_campaign' => 'Updated GA campaign',
    ];

    $response = $this->endpoint->save($newsletterData);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $updatedNewsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter); // PHPStan
    verify($response->data)->equals($this->newslettersResponseBuilder->build($updatedNewsletter, [NewslettersResponseBuilder::RELATION_SEGMENTS]));
    verify($updatedNewsletter->getType())->equals('Updated type');
    verify($updatedNewsletter->getSubject())->equals('Updated subject');
    verify($updatedNewsletter->getPreheader())->equals('Updated preheader');
    verify($updatedNewsletter->getBody())->equals(['value' => 'Updated body']);
    verify($updatedNewsletter->getSenderName())->equals('Updated sender name');
    verify($updatedNewsletter->getSenderAddress())->equals('Updated sender address');
    verify($updatedNewsletter->getReplyToName())->equals('Updated reply-to name');
    verify($updatedNewsletter->getReplyToAddress())->equals('Updated reply-to address');
    verify($updatedNewsletter->getGaCampaign())->equals('Updated GA campaign');
  }

  public function testItCanSaveAndLoadArchiveVisibilityOption(): void {
    (new NewsletterOption())
      ->create($this->newsletter, NewsletterOptionFieldEntity::NAME_EXCLUDE_FROM_ARCHIVE, '0');

    $response = $this->endpoint->save([
      'id' => $this->newsletter->getId(),
      'type' => NewsletterEntity::TYPE_STANDARD,
      'subject' => $this->newsletter->getSubject(),
      'options' => [
        NewsletterOptionFieldEntity::NAME_EXCLUDE_FROM_ARCHIVE => '1',
      ],
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['options'][NewsletterOptionFieldEntity::NAME_EXCLUDE_FROM_ARCHIVE])->equals('1');

    $response = $this->endpoint->save([
      'id' => $this->newsletter->getId(),
      'type' => NewsletterEntity::TYPE_STANDARD,
      'subject' => $this->newsletter->getSubject(),
      'options' => [
        NewsletterOptionFieldEntity::NAME_EXCLUDE_FROM_ARCHIVE => '0',
      ],
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['options'][NewsletterOptionFieldEntity::NAME_EXCLUDE_FROM_ARCHIVE])->equals('0');
  }

  public function testItCanUpdateShareVisibilityToPrivate(): void {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();

    $response = $this->endpoint->updateShareVisibility([
      'id' => $newsletter->getId(),
      'share_visibility' => ShareVisibility::VISIBILITY_PRIVATE,
    ]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['share_visibility'])->equals(ShareVisibility::VISIBILITY_PRIVATE);
    verify($response->data['effective_share_visibility'])->equals(ShareVisibility::VISIBILITY_PRIVATE);
    verify($response->data['can_share'])->false();

    $this->newsletterRepository->refresh($newsletter);
    verify($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY))
      ->equals(ShareVisibility::VISIBILITY_PRIVATE);
  }

  public function testItCanRestoreANewsletter() {
    $this->newsletterRepository->bulkTrash([$this->newsletter->getId()]);
    $this->entityManager->clear();

    $trashedNewsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $trashedNewsletter);
    verify($trashedNewsletter->getDeletedAt())->notNull();

    $response = $this->endpoint->restore(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
    verify($response->data['deleted_at'])->null();
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanTrashANewsletter() {
    $response = $this->endpoint->trash(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
    verify($response->data['deleted_at'])->notNull();
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteANewsletter() {
    $response = $this->endpoint->delete(['id' => $this->newsletter->getId()]);
    verify($response->data)->empty();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanCreateANewsletter() {
    $data = [
      'subject' => 'My New Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];
    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneBy(['subject' => 'My New Newsletter']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));

    $response = $this->endpoint->create();
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals('Please specify a type.');
  }

  public function testItCanCreateAnAutomationNewsletter() {
    $data = [
      'subject' => 'My Automation newsletter',
      'type' => NewsletterEntity::TYPE_AUTOMATION,
    ];
    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneBy(['subject' => 'My Automation newsletter']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
  }

  public function testItCanCreateAnAutomationNewsletterForBlockEditor() {
    $data = [
      'subject' => 'My Automation block newsletter',
      'type' => NewsletterEntity::TYPE_AUTOMATION,
      'new_editor' => true,
    ];
    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneBy(['subject' => 'My Automation block newsletter']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertIsInt($newsletter->getWpPostId());
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
    verify($response->data['wp_post_id'])->equals($newsletter->getWpPostId());
  }

  public function testItHasDefaultSenderAfterCreate() {
    $data = [
      'subject' => 'My First Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];

    $settingsController = $this->diContainer->get(SettingsController::class);
    $settingsController->set('sender', ['name' => 'Sender', 'address' => 'sender@test.com']);
    $settingsController->set('reply_to', ['name' => 'Reply', 'address' => 'reply@test.com']);

    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['subject'])->equals('My First Newsletter');
    verify($response->data['type'])->equals(NewsletterEntity::TYPE_STANDARD);
    verify($response->data['sender_address'])->equals('sender@test.com');
    verify($response->data['sender_name'])->equals('Sender');
    verify($response->data['reply_to_address'])->equals('reply@test.com');
    verify($response->data['reply_to_name'])->equals('Reply');
  }

  public function testItCanSendAPreview() {
    $subscriber = 'test@subscriber.com';
    $endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'sendPreviewController' => $this->make(SendPreviewController::class, [
        'sendPreview' => null,
      ]),
    ]);

    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->getId(),
    ];
    $response = $endpoint->sendPreview($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItReturnsMailerErrorWhenSendingFailed() {
    $subscriber = 'test@subscriber.com';
    $endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'sendPreviewController' => $this->make(SendPreviewController::class, [
        'sendPreview' => Expected::once(function () {
          throw new SendPreviewException('The email could not be sent: failed');
        }),
      ]),
    ]);

    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->getId(),
    ];
    $response = $endpoint->sendPreview($data);
    verify($response->errors[0]['message'])->equals('The email could not be sent: failed');
  }

  public function testItReturnsBrowserPreviewUrlWithoutProtocol() {
    $data = [
      'id' => $this->newsletter->getId(),
      'body' => 'fake body',
    ];

    $emoji = $this->make(
      Emoji::class,
      ['encodeForUTF8Column' => Expected::once(function ($params) {
        return $params;
      })]
    );

    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $this->endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'wp' => $wp,
      'emoji' => $emoji,
    ]);

    $response = $this->endpoint->showPreview($data);
    verify($response->meta['preview_url'])->stringNotContainsString('http');
    verify($response->meta['preview_url'])->stringMatchesRegExp('!^\/\/!');
  }
}
