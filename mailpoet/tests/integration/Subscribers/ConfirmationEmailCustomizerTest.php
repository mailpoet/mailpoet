<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Util\pQuery\pQuery;

class ConfirmationEmailCustomizerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  private $partialTemplateContent = 'Please confirm your subscription to receive emails from us';

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->newsletterFactory = new NewsletterFactory();
  }

  public function testItGeneratesNewsletterOnInit() {
    $controller = $this->generateController();

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->equals(false);
    $controller->init();

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->notEquals(false);
  }

  public function testItGenerateNewsletterIfNoneExist() {
    $controller = $this->generateController();

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->equals(false);
    $newsletter = $controller->getNewsletter();

    verify($newsletter)->instanceOf(NewsletterEntity::class);

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->notEquals(false);
  }

  public function testItRegenerateNewsletterIfIdIsSetButNewsletterDoesNotExist() {
    $this->settings->set(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, 5);

    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    verify($newsletter)->instanceOf(NewsletterEntity::class);

    verify($newsletter->getId())->notEquals(5);

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->equals($newsletter->getId());
  }

  public function testItGenerateNewsletterOfTypeConfirmationEmail() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    verify($newsletter->getType())->equals(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER);
  }

  public function testItFetchAlreadyCreatedNewsletter() {
    $newsletter = $this->newsletterFactory
      ->loadBodyFrom('newsletterThreeCols.json')
      ->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)
      ->create();

    $this->settings->set(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, $newsletter->getId());

    $controller = $this->generateController();
    $newNewsletter = $controller->getNewsletter();

    verify($newNewsletter->getId())->equals($newsletter->getId());
  }

  public function testItFetchesConfirmationEmailTemplate() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    verify($newsletter->getBody())->isArray();
    $stringBody = json_encode($newsletter->getBody());
    verify($stringBody)->stringContainsString($this->partialTemplateContent);
  }

  public function testItRendersEmail() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    $renderedContent = $controller->render($newsletter);

    verify($renderedContent)->isArray();

    verify($renderedContent)->arrayHasKey('html');
    verify($renderedContent)->arrayHasKey('text');
    verify($renderedContent)->arrayHasKey('subject');
  }

  public function testItRendersEmailWithDefaultTemplateContent() {
    $subject = 'Confirm your subscription';
    $this->settings->set('signup_confirmation.subject', $subject);

    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    $renderedContent = (array)$controller->render($newsletter);

    verify($renderedContent['html'])->stringContainsString($this->partialTemplateContent);
    verify($renderedContent['text'])->stringContainsString($this->partialTemplateContent);
    verify($renderedContent['subject'])->stringContainsString($subject);
  }

  public function testItRendersDefaultTemplateWithGlobalContentAndFooterStyles() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();
    $body = $newsletter->getBody();
    $body['globalStyles']['wrapper']['backgroundColor'] = '#f0e1d2';
    $body['globalStyles']['text']['fontColor'] = '#b00020';
    $body['globalStyles']['text']['fontFamily'] = 'Permanent Marker';
    $newsletter->setBody($body);

    $renderedContent = (array)$controller->render($newsletter);
    $html = $renderedContent['html'];
    $DOM = (new pQuery())->parseStr($html);
    $contentWrapperStyle = $DOM('table.mailpoet_content-wrapper', 0)->attr('style');
    $footerStyle = $DOM('td.mailpoet_footer', 0)->attr('style');

    verify($contentWrapperStyle)->stringContainsString('background-color:#f0e1d2');
    verify($footerStyle)->stringContainsString('color:#b00020');
    verify($footerStyle)->stringContainsString('Permanent Marker');
    verify($html)->stringNotContainsString('background-color:#ffffff!important');
  }

  private function generateController(): ConfirmationEmailCustomizer {
    return $this->diContainer->get(ConfirmationEmailCustomizer::class);
  }
}
