<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\FreeDomains;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class SenderDomainPausedCampaignsNotice {
  private const MAX_LISTED_CAMPAIGNS = 3;

  private WPFunctions $wp;

  private ScheduledTasksRepository $scheduledTasksRepository;

  private FreeDomains $freeDomains;

  private SettingsController $settings;

  private AuthorizedSenderDomainController $senderDomainController;

  public function __construct(
    WPFunctions $wp,
    ScheduledTasksRepository $scheduledTasksRepository,
    FreeDomains $freeDomains,
    SettingsController $settings,
    AuthorizedSenderDomainController $senderDomainController
  ) {
    $this->wp = $wp;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->freeDomains = $freeDomains;
    $this->settings = $settings;
    $this->senderDomainController = $senderDomainController;
  }

  public function init(bool $shouldDisplay): ?Notice {
    if (
      !$shouldDisplay
      || $this->settings->get('mta.method') !== Mailer::METHOD_MAILPOET
      || !$this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS)
    ) {
      return null;
    }

    $newsletters = $this->getPausedNewsletters();
    if (!$newsletters) {
      return null;
    }

    return Notice::displayError(
      $this->getMessage($newsletters),
      'mailpoet-sender-domain-paused-campaigns-notice',
      '',
      false,
      false
    );
  }

  /**
   * @return NewsletterEntity[]
   */
  private function getPausedNewsletters(): array {
    $newsletters = [];
    foreach ($this->scheduledTasksRepository->findPausedForSenderDomain() as $task) {
      $queue = $task->getSendingQueue();
      $newsletter = $queue ? $queue->getNewsletter() : null;
      // A timezone campaign has one task per batch, list it once
      if ($newsletter instanceof NewsletterEntity) {
        $newsletters[$newsletter->getId()] = $newsletters[$newsletter->getId()] ?? $newsletter;
      }
    }
    return array_values($newsletters);
  }

  /**
   * @param NewsletterEntity[] $newsletters
   */
  private function getMessage(array $newsletters): string {
    $groups = [];
    foreach ($newsletters as $newsletter) {
      $senderAddress = (string)$newsletter->getSenderAddress();
      $domain = Helpers::extractEmailDomain($senderAddress);
      $groups[$domain]['senderAddress'] = $groups[$domain]['senderAddress'] ?? $senderAddress;
      $groups[$domain]['newsletters'][] = $newsletter;
    }

    $branded = [];
    $freeMail = [];
    $generic = [];
    foreach ($groups as $domain => $group) {
      $domain = (string)$domain;
      if ($domain === '') {
        $generic[] = $this->renderGenericGroup($group['newsletters']);
      } elseif ($this->freeDomains->isEmailOnFreeDomain($domain)) {
        $freeMail[] = $this->renderFreeMailGroup($domain, $group['newsletters']);
      } else {
        $branded[] = $this->renderBrandedGroup($domain, $group['senderAddress'], $group['newsletters']);
      }
    }

    return implode('', array_merge($branded, $freeMail, $generic));
  }

  /**
   * @param NewsletterEntity[] $newsletters
   */
  private function renderBrandedGroup(string $domain, string $senderAddress, array $newsletters): string {
    $isBigSender = $this->senderDomainController->isBigSender();
    $count = count($newsletters);
    if ($isBigSender) {
      // translators: %1$d is the number of paused campaigns, %2$s is the list of campaign names, %3$s is a domain name, %4$d is a number of subscribers
      $text = _n(
        '%1$d campaign is paused: %2$s. Your site has more than %4$d subscribers, so campaigns must be sent from an authenticated domain. Authenticate %3$s or change the From address, then resume it.',
        '%1$d campaigns are paused: %2$s. Your site has more than %4$d subscribers, so campaigns must be sent from an authenticated domain. Authenticate %3$s or change the From address, then resume them.',
        $count,
        'mailpoet'
      );
    } else {
      // translators: %1$d is the number of paused campaigns, %2$s is the list of campaign names, %3$s is a domain name
      $text = _n(
        '%1$d campaign is paused: %2$s. Campaigns must be sent from an authenticated domain. Authenticate %3$s or change the From address, then resume it.',
        '%1$d campaigns are paused: %2$s. Campaigns must be sent from an authenticated domain. Authenticate %3$s or change the From address, then resume them.',
        $count,
        'mailpoet'
      );
    }

    $button = sprintf(
      '<a href="#" class="button button-primary mailpoet-js-button-authorize-email-and-sender-domain" data-email="%s" data-type="domain">%s</a>',
      $this->wp->escAttr($senderAddress),
      $this->wp->escHtml(__('Authenticate domain', 'mailpoet'))
    );

    return '<p>' . $this->formatText($text, $newsletters, $domain) . '<br>' . $button . '</p>';
  }

  /**
   * @param NewsletterEntity[] $newsletters
   */
  private function renderFreeMailGroup(string $domain, array $newsletters): string {
    $isBigSender = $this->senderDomainController->isBigSender();
    $count = count($newsletters);
    if ($isBigSender) {
      // translators: %1$d is the number of paused campaigns, %2$s is the list of campaign names, %3$s is a shared domain name such as gmail.com, %4$d is a number of subscribers
      $text = _n(
        '%1$d campaign is paused: %2$s. Your site has more than %4$d subscribers, so campaigns can’t be sent from a shared address like %3$s. Change the From address to one on your own domain, then resume it.',
        '%1$d campaigns are paused: %2$s. Your site has more than %4$d subscribers, so campaigns can’t be sent from a shared address like %3$s. Change the From address to one on your own domain, then resume them.',
        $count,
        'mailpoet'
      );
    } else {
      // translators: %1$d is the number of paused campaigns, %2$s is the list of campaign names, %3$s is a shared domain name such as gmail.com
      $text = _n(
        '%1$d campaign is paused: %2$s. Campaigns can’t be sent from a shared address like %3$s. Change the From address to one on your own domain, then resume it.',
        '%1$d campaigns are paused: %2$s. Campaigns can’t be sent from a shared address like %3$s. Change the From address to one on your own domain, then resume them.',
        $count,
        'mailpoet'
      );
    }
    return '<p>' . $this->formatText($text, $newsletters, $domain) . '</p>';
  }

  /**
   * @param NewsletterEntity[] $newsletters
   */
  private function renderGenericGroup(array $newsletters): string {
    $isBigSender = $this->senderDomainController->isBigSender();
    $count = count($newsletters);
    if ($isBigSender) {
      // translators: %1$d is the number of paused campaigns, %2$s is the list of campaign names, %4$d is a number of subscribers
      $text = _n(
        '%1$d campaign is paused: %2$s. Your site has more than %4$d subscribers, so campaigns must be sent from an authenticated domain. Change the From address, then resume it.',
        '%1$d campaigns are paused: %2$s. Your site has more than %4$d subscribers, so campaigns must be sent from an authenticated domain. Change the From address, then resume them.',
        $count,
        'mailpoet'
      );
    } else {
      // translators: %1$d is the number of paused campaigns, %2$s is the list of campaign names
      $text = _n(
        '%1$d campaign is paused: %2$s. Campaigns must be sent from an authenticated domain. Change the From address, then resume it.',
        '%1$d campaigns are paused: %2$s. Campaigns must be sent from an authenticated domain. Change the From address, then resume them.',
        $count,
        'mailpoet'
      );
    }
    return '<p>' . $this->formatText($text, $newsletters, '') . '</p>';
  }

  /**
   * @param NewsletterEntity[] $newsletters
   */
  private function formatText(string $text, array $newsletters, string $domain): string {
    return sprintf(
      $text,
      count($newsletters),
      $this->renderCampaignList($newsletters),
      $this->wp->escHtml($domain),
      AuthorizedSenderDomainController::UPPER_LIMIT
    );
  }

  /**
   * @param NewsletterEntity[] $newsletters
   */
  private function renderCampaignList(array $newsletters): string {
    $links = [];
    foreach (array_slice($newsletters, 0, self::MAX_LISTED_CAMPAIGNS) as $newsletter) {
      $name = $newsletter->getCampaignNameOrSubject() ?: __('(No subject)', 'mailpoet');
      $links[] = sprintf(
        '“<a href="%s">%s</a>”',
        $this->wp->escUrl($this->getResumeUrl($newsletter)),
        $this->wp->escHtml($name)
      );
    }
    $list = implode(', ', $links);

    $rest = count($newsletters) - self::MAX_LISTED_CAMPAIGNS;
    if ($rest > 0) {
      $list .= sprintf(
        ' <a href="%s">%s</a>',
        $this->wp->escUrl($this->wp->adminUrl('admin.php?page=mailpoet-newsletters')),
        $this->wp->escHtml(sprintf(
          // translators: %d is the number of paused campaigns that are not listed by name
          _n('and %d more', 'and %d more', $rest, 'mailpoet'),
          $rest
        ))
      );
    }
    return $list;
  }

  private function getResumeUrl(NewsletterEntity $newsletter): string {
    $parent = $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY ? $newsletter->getParent() : null;
    $route = $parent
      ? '#/notification/history/' . $parent->getId()
      : '#/send/' . $newsletter->getId();
    return $this->wp->adminUrl('admin.php?page=mailpoet-newsletters' . $route);
  }
}
