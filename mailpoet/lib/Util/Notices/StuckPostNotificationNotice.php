<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util\Notices;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class StuckPostNotificationNotice {

  /** @var WPFunctions */
  private $wp;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    WPFunctions $wp,
    NewslettersRepository $newslettersRepository
  ) {
    $this->wp = $wp;
    $this->newslettersRepository = $newslettersRepository;
  }

  public function init(bool $shouldDisplay): void {
    if (!$shouldDisplay) {
      return;
    }
    $stuckParents = $this->newslettersRepository->findStuckPostNotificationParents();
    if (empty($stuckParents)) {
      return;
    }
    $this->display($stuckParents);
  }

  /**
   * @param array<int, array{parent: NewsletterEntity, hasInvalid: bool}> $stuckParents
   */
  private function display(array $stuckParents): void {
    Notice::displayWarning($this->getMessage($stuckParents), '', '', false);
  }

  /**
   * @param array<int, array{parent: NewsletterEntity, hasInvalid: bool}> $stuckParents
   */
  private function getMessage(array $stuckParents): string {
    $count = count($stuckParents);
    $heading = sprintf(
      '<p><b>%s</b></p>',
      $this->wp->escHtml(_n(
        'A post notification is stuck and may not have reached all of your subscribers.',
        'Some post notifications are stuck and may not have reached all of your subscribers.',
        $count,
        'mailpoet'
      ))
    );

    $items = '';
    foreach ($stuckParents as $entry) {
      $items .= '<li>' . $this->renderItem($entry['parent'], $entry['hasInvalid']) . '</li>';
    }

    return $heading . '<ul>' . $items . '</ul>';
  }

  private function renderItem(NewsletterEntity $parent, bool $hasInvalid): string {
    $reason = $hasInvalid
      ? __('flagged as invalid', 'mailpoet')
      : __('paused', 'mailpoet');

    $historyUrl = $this->wp->adminUrl(
      'admin.php?page=mailpoet-newsletters#/notification/history/' . $parent->getId()
    );

    $line = sprintf(
      // translators: %1$s is the post notification subject, %2$s is the status (paused / flagged as invalid)
      __('"%1$s" is %2$s.', 'mailpoet'),
      $this->wp->escHtml($parent->getSubject()),
      $this->wp->escHtml($reason)
    );

    $link = sprintf(
      ' <a href="%s">%s</a>',
      $this->wp->escUrl($historyUrl),
      $this->wp->escHtml(__('View sending history', 'mailpoet'))
    );

    return $line . $link;
  }
}
