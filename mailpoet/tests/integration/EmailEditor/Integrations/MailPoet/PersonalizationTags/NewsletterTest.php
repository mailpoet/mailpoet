<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Newsletter;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class NewsletterTest extends \MailPoetTest {
  private Newsletter $newsletter;

  public function _before() {
    parent::_before();
    $this->newsletter = $this->diContainer->get(Newsletter::class);
  }

  public function testItReturnsNewsletterSubject(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Weekly digest')
      ->create();

    $result = $this->newsletter->getSubject(['newsletter_id' => $newsletter->getId()]);

    $this->assertSame('Weekly digest', $result);
  }

  public function testItReturnsEmptyStringWhenNewsletterIsMissing(): void {
    $result = $this->newsletter->getSubject(['newsletter_id' => 0]);

    $this->assertSame('', $result);
  }
}
