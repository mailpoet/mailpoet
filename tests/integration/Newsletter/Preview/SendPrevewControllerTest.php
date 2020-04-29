<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Url;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class SendPrevewControllerTest extends \MailPoetTest {
  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var Newsletter */
  private $newsletter;

  public function _before() {
    parent::_before();
    $this->subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'My Standard Newsletter',
      'preheader' => 'preheader',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_STANDARD,
    ]);

  }

  public function testItCanSendAPreview() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Expected::once(
        function ($newsletter, $subscriber, $extraParams) {
          $unsubscribeLink = $this->subscriptionUrlFactory->getConfirmUnsubscribeUrl(null);
          $manageLink = $this->subscriptionUrlFactory->getManageUrl(null);
          $viewInBrowserLink = Url::getViewInBrowserUrl($this->newsletter);
          $mailerMetaInfo = new MetaInfo;

          expect(is_array($newsletter))->true();
          expect($newsletter['body']['text'])->contains('Hello test');
          expect($subscriber)->equals($subscriber);
          expect($extraParams['unsubscribe_url'])->equals(home_url());
          expect($extraParams['meta'])->equals($mailerMetaInfo->getPreviewMetaInfo());

          // system links are replaced with hashes
          expect($newsletter['body']['html'])->contains('href="' . $viewInBrowserLink . '">View in browser');
          expect($newsletter['body']['html'])->contains('href="' . $unsubscribeLink . '">Unsubscribe');
          expect($newsletter['body']['html'])->contains('href="' . $manageLink . '">Manage subscription');
          return ['response' => true];
        }
      ),
    ]);

    $sendPreviewController = new SendPreviewController($mailer, new MetaInfo(), new WPFunctions());
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }

  public function testItThrowsWhenSendingFailed() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => function ($newsletter, $subscriber) {
        expect(is_array($newsletter))->true();
        expect($newsletter['body']['text'])->contains('Hello test');
        expect($subscriber)->equals($subscriber);
        return [
          'response' => false,
          'error' => $this->make(MailerError::class, [
            'getMessage' => 'failed',
          ]),
        ];
      },
    ]);

    $this->expectException(SendPreviewException::class);
    $this->expectExceptionMessage('The email could not be sent: failed');

    $sendPreviewController = new SendPreviewController($mailer, new MetaInfo(), new WPFunctions());
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }
}
