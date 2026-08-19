<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;

/**
 * WorkerTest and AutomatedEmailsTest both mock the Renderer, so nothing else
 * actually renders these templates — a Twig error in one of them would ship
 * unnoticed. This renders all six for real, with and without untracked
 * recipients, so both branches of the not-tracked line are exercised.
 */
class StatsNotificationTemplatesTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = $this->diContainer->get(Renderer::class);
  }

  public function testItRendersTheCampaignDigestWithACoverageLine() {
    foreach (['emails/statsNotification.html', 'emails/statsNotificationGarden.html', 'emails/statsNotification.txt'] as $template) {
      $output = $this->renderer->render($template, $this->campaignContext(37, 463));
      verify($output)->stringContainsString('37');
      verify($output)->stringContainsString('not tracked');
      // The line names the denominator the rates actually used, so the reader
      // is never left to infer it from a percentage.
      verify($output)->stringContainsString('463');
      verify($output)->stringNotContainsString('%%');
    }
  }

  public function testItRendersTheCampaignDigestWithoutACoverageLineWhenNothingIsUntracked() {
    foreach (['emails/statsNotification.html', 'emails/statsNotificationGarden.html', 'emails/statsNotification.txt'] as $template) {
      $output = $this->renderer->render($template, $this->campaignContext(0, 500));
      verify($output)->stringNotContainsString('not tracked');
    }
  }

  public function testItRendersTheAutomatedDigestWithACoverageLine() {
    $templates = [
      'emails/statsNotificationAutomatedEmails.html',
      'emails/statsNotificationAutomatedEmailsGarden.html',
      'emails/statsNotificationAutomatedEmails.txt',
    ];
    foreach ($templates as $template) {
      $output = $this->renderer->render($template, $this->automatedContext(12, 88));
      verify($output)->stringContainsString('12');
      verify($output)->stringContainsString('not tracked');
      verify($output)->stringContainsString('88');
      verify($output)->stringNotContainsString('%%');
    }
  }

  public function testItRendersTheAutomatedDigestWithoutACoverageLineWhenNothingIsUntracked() {
    $templates = [
      'emails/statsNotificationAutomatedEmails.html',
      'emails/statsNotificationAutomatedEmailsGarden.html',
      'emails/statsNotificationAutomatedEmails.txt',
    ];
    foreach ($templates as $template) {
      $output = $this->renderer->render($template, $this->automatedContext(0, 500));
      verify($output)->stringNotContainsString('not tracked');
    }
  }

  private function campaignContext(int $notTracked, int $trackedSent): array {
    return array_merge($this->statBlock($notTracked, $trackedSent), [
      'subject' => 'Test campaign',
      'preheader' => 'preheader',
      'topLinkClicks' => 2,
      'topLink' => 'https://example.com',
      'linkSettings' => 'https://example.com/settings',
      'linkStats' => 'https://example.com/stats',
      'subscribersLimitReached' => false,
      'hasValidApiKey' => true,
      'subscribersLimit' => 1000,
      'upgradeNowLink' => 'https://example.com/upgrade',
      'blogName' => 'Test blog',
      'recipientFirstName' => 'Admin',
    ]);
  }

  private function automatedContext(int $notTracked, int $trackedSent): array {
    return [
      'linkSettings' => 'https://example.com/settings',
      'blogName' => 'Test blog',
      'recipientFirstName' => 'Admin',
      'newsletters' => [
        array_merge($this->statBlock($notTracked, $trackedSent), [
          'linkStats' => 'https://example.com/stats',
          'subject' => 'Test automation email',
        ]),
      ],
    ];
  }

  private function statBlock(int $notTracked, int $trackedSent): array {
    return [
      'clicked' => 12.5,
      'opened' => 40.0,
      'machineOpened' => 3.0,
      'unsubscribed' => 1.0,
      'bounced' => 0.5,
      'notTracked' => $notTracked,
      'trackedSent' => $trackedSent,
    ];
  }
}
