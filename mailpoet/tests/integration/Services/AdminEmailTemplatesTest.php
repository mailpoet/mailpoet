<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use MailPoet\Config\Renderer;

/**
 * The unit and mailer tests mock the Renderer, so nothing else renders these
 * templates for real — a Twig error in one of them would ship unnoticed.
 */
class AdminEmailTemplatesTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = $this->diContainer->get(Renderer::class);
  }

  public function testItRendersTheCongratulatoryMssEmail() {
    foreach (['emails/congratulatoryMssEmail.html', 'emails/congratulatoryMssEmail.txt'] as $template) {
      $output = $this->renderer->render($template);
      verify($output)->stringContainsString('MailPoet is now sending your emails');
      verify($output)->stringContainsString('This email was sent automatically with the MailPoet Sending Service');
    }
    verify($this->renderer->render('emails/congratulatoryMssEmail.html'))->stringContainsString('logo-orange-400x122.png');
  }

  public function testItRendersTheNewSubscriberNotification() {
    $context = [
      'subscriber_email' => 'subscriber@example.com',
      'segments_names' => 'Newsletter list',
      'link_settings' => 'https://example.com/settings',
      'link_premium' => 'https://example.com/premium',
    ];
    foreach (['emails/newSubscriberNotification.html', 'emails/newSubscriberNotification.txt'] as $template) {
      $output = $this->renderer->render($template, $context);
      verify($output)->stringContainsString('subscriber@example.com');
      verify($output)->stringContainsString('Newsletter list');
      verify($output)->stringContainsString('The MailPoet Plugin');
      verify($output)->stringContainsString('MailPoet Settings');
      verify($output)->stringContainsString('https://example.com/settings');
    }
  }
}
