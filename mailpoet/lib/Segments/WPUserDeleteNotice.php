<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments;

use MailPoet\Config\AccessControl;
use MailPoet\Config\Menu;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Tells the admin, on the WordPress "Delete Users" confirmation screen, that deleting a
 * WordPress user does not delete the MailPoet subscriber linked to it.
 *
 * Before MailPoet 5.26.0 the subscriber was hard-deleted along with the user. It is now
 * unlinked and kept instead - see WP::unlinkSubscriberFromWpUser(). WordPress users are
 * often deleted for privacy reasons, so the change is worth surfacing at the moment of
 * deletion rather than only in the documentation.
 *
 * The notice names the Trash on purpose. A subscriber who was only on the WP Users list
 * and is not a WooCommerce customer is trashed rather than left on a list, and the
 * subscribers listing hides trashed rows from every group except "trash". Without that
 * pointer an admin checks the listing, finds nothing, and concludes the subscriber was
 * deleted after all - which is the opposite of what this notice is for.
 *
 * Sites that opted back into the old hard-delete behaviour with the
 * `mailpoet_delete_subscriber_on_wp_user_delete` filter get what they expect already, so
 * they are not counted and the notice stays hidden.
 */
class WPUserDeleteNotice {
  const KB_ARTICLE_URL = 'https://kb.mailpoet.com/article/133-the-wordpress-users-list#remove-users-from-wordpress-list';

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var AccessControl */
  private $accessControl;

  public function __construct(
    WPFunctions $wp,
    SubscribersRepository $subscribersRepository,
    AccessControl $accessControl
  ) {
    $this->wp = $wp;
    $this->subscribersRepository = $subscribersRepository;
    $this->accessControl = $accessControl;
  }

  public function setupHooks(): void {
    // Fires at the end of the delete-users confirmation form, just before the confirm
    // button. Used by both wp-admin/users.php (single site) and confirm_delete_users()
    // in wp-admin/includes/ms.php (network admin).
    $this->wp->addAction('delete_user_form', [$this, 'displayNotice'], 10, 2);
  }

  /**
   * @param \WP_User|null $currentUser Part of the delete_user_form signature, unused.
   * @param mixed $userIds IDs of the users about to be deleted. Network admin passes raw
   *                       $_POST values, so this is not guaranteed to hold clean integers.
   */
  public function displayNotice($currentUser = null, $userIds = null): void {
    if (!is_array($userIds)) {
      return;
    }

    $userIds = $this->normalizeUserIds($userIds);
    $count = $this->countSubscribersKeptOnDelete($userIds);
    if ($count < 1) {
      return;
    }
    $onlyOneUserSelected = count($userIds) === 1;

    $allowedHtml = [
      'strong' => [],
      'a' => [
        'href' => true,
        'target' => true,
        'rel' => true,
      ],
    ];

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<p>' . $this->wp->wpKses($this->getMessage($count, $onlyOneUserSelected), $allowedHtml) . '</p>';
  }

  /**
   * @param array $userIds Raw ids, which on the network admin screen come straight from $_POST.
   * @return int[]
   */
  private function normalizeUserIds(array $userIds): array {
    $normalized = [];
    foreach ($userIds as $userId) {
      $userId = is_scalar($userId) ? (int)$userId : 0;
      if ($userId > 0) {
        $normalized[] = $userId;
      }
    }
    return array_values(array_unique($normalized));
  }

  /**
   * Number of the users about to be deleted whose MailPoet subscriber will survive.
   *
   * @param int[] $userIds Already normalised by displayNotice().
   */
  private function countSubscribersKeptOnDelete(array $userIds): int {
    if (!$userIds) {
      return 0;
    }

    $subscribers = $this->subscribersRepository->findBy(['wpUserId' => $userIds]);

    $count = 0;
    foreach ($subscribers as $subscriber) {
      $wpUserId = $subscriber->getWpUserId();
      if ($wpUserId === null) {
        continue;
      }
      // Same filter call as WP::unlinkSubscriberFromWpUser(), so the count reflects what
      // will actually happen on this site rather than the default behaviour.
      $hardDelete = (bool)$this->wp->applyFilters(
        'mailpoet_delete_subscriber_on_wp_user_delete',
        false,
        $subscriber,
        (int)$wpUserId
      );
      if (!$hardDelete) {
        $count++;
      }
    }

    return $count;
  }

  private function getMessage(int $count, bool $onlyOneUserSelected): string {
    if ($onlyOneUserSelected) {
      // WordPress already says "You have specified this user for deletion", so a count
      // here would only read as noise.
      $message = __(
        '<strong>MailPoet:</strong> This user is also a MailPoet subscriber. Deleting a WordPress user does not delete their MailPoet subscriber. The subscriber is removed from the "WordPress Users" list but kept in MailPoet, so look for them on their other lists or in the Trash.',
        'mailpoet'
      );
    } else {
      // translators: %d is the number of users being deleted who are also MailPoet subscribers.
      $notice = _n(
        '<strong>MailPoet:</strong> %d of the users you are deleting is also a MailPoet subscriber. Deleting a WordPress user does not delete their MailPoet subscriber. The subscriber is removed from the "WordPress Users" list but kept in MailPoet, so look for them on their other lists or in the Trash.',
        '<strong>MailPoet:</strong> %d of the users you are deleting are also MailPoet subscribers. Deleting a WordPress user does not delete their MailPoet subscriber. The subscribers are removed from the "WordPress Users" list but kept in MailPoet, so look for them on their other lists or in the Trash.',
        $count,
        'mailpoet'
      );
      $message = sprintf($notice, $count);
    }

    $links = [];

    if ($this->accessControl->validatePermission(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS)) {
      $links[] = Helpers::replaceLinkTags(
        __('[link]Manage MailPoet subscribers[/link]', 'mailpoet'),
        $this->wp->adminUrl('admin.php?page=' . Menu::SUBSCRIBERS_PAGE_SLUG)
      );
    }

    $links[] = Helpers::replaceLinkTags(
      __('[link]Read more about the WordPress Users list[/link]', 'mailpoet'),
      self::KB_ARTICLE_URL,
      ['target' => '_blank', 'rel' => 'noopener noreferrer']
    );

    return $message . ' ' . implode(' | ', $links);
  }
}
