<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Integrations\MailPoet\Templates\ClassicTemplateEmails;
use MailPoet\Automation\Integrations\MailPoet\Templates\TemplateEmailContent;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoetTest;

class TemplateEmailContentTest extends MailPoetTest {
  private TemplateEmailContent $templateEmailContent;

  private NewslettersRepository $newslettersRepository;

  public function _before(): void {
    parent::_before();
    $this->templateEmailContent = $this->diContainer->get(TemplateEmailContent::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testItAppliesClassicContent(): void {
    $newsletter = $this->createAutomationEmail();

    $this->templateEmailContent->apply($newsletter, 'welcome-email-content');

    $this->newslettersRepository->refresh($newsletter);
    $body = $newsletter->getBody();
    $this->assertIsArray($body);
    $this->assertSame('container', $body['content']['type']);
    $this->assertStringContainsString('[link:subscription_unsubscribe_url]', (string)json_encode($body));
  }

  public function testItAppliesBlockContent(): void {
    $newsletter = $this->createAutomationEmail();
    $wpPostId = wp_insert_post([
      'post_type' => 'mailpoet_email',
      'post_status' => 'private',
      'post_content' => '',
    ]);
    $this->assertIsInt($wpPostId);
    $newsletter->setWpPost($this->entityManager->getReference(WpPostEntity::class, $wpPostId));
    $this->newslettersRepository->flush();

    $this->templateEmailContent->apply($newsletter, 'welcome-email-content');

    $wpPost = get_post($wpPostId);
    $this->assertInstanceOf(\WP_Post::class, $wpPost);
    $this->assertNotEmpty($wpPost->post_content); // @phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->assertEquals('newsletter', get_post_meta($wpPostId, '_wp_page_template', true));
    $this->assertNull($newsletter->getBody());
  }

  public function testItThrowsForUnknownPattern(): void {
    $newsletter = $this->createAutomationEmail();

    $this->expectException(NotFoundException::class);
    $this->templateEmailContent->apply($newsletter, 'non-existent-pattern');
  }

  /**
   * @dataProvider templatePatterns
   */
  public function testEveryTemplatePatternHasClassicContent(string $pattern): void {
    $this->assertIsArray((new ClassicTemplateEmails())->getBody($pattern));
  }

  public function templatePatterns(): array {
    return [
      ['welcome-email-content'],
      ['birthday-email-content'],
      ['birthday-email-with-discount'],
      ['first-purchase-thank-you'],
      ['post-purchase-thank-you'],
      ['abandoned-cart-content'],
      ['product-purchase-follow-up'],
      ['tag-purchase-follow-up'],
      ['category-purchase-follow-up'],
    ];
  }

  private function createAutomationEmail(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setSubject('Template email');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }
}
