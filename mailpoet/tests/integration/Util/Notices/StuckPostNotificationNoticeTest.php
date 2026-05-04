<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class StuckPostNotificationNoticeTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var NewslettersRepository&\PHPUnit\Framework\MockObject\MockObject */
  private $newslettersRepository;

  public function _before() {
    parent::_before();
    $this->wp = new WPFunctions();
    $this->wp->deleteTransient(StuckPostNotificationNotice::OPTION_NAME);
    $this->newslettersRepository = $this->createMock(NewslettersRepository::class);
  }

  public function _after() {
    $this->wp->deleteTransient(StuckPostNotificationNotice::OPTION_NAME);
    parent::_after();
  }

  public function testItReturnsNullWhenShouldDisplayIsFalse() {
    $this->newslettersRepository->expects($this->never())->method('findStuckPostNotificationParents');
    $notice = $this->createNotice();

    verify($notice->init(false))->null();
  }

  public function testItReturnsNullWhenNoStuckParentsExist() {
    $this->newslettersRepository->method('findStuckPostNotificationParents')->willReturn([]);
    $notice = $this->createNotice();

    verify($notice->init(true))->null();
  }

  public function testItRendersSingularHeadingForOneStuckParent() {
    $this->newslettersRepository->method('findStuckPostNotificationParents')->willReturn([
      ['parent' => $this->makeParent(1, 'Weekly digest'), 'hasInvalid' => false],
    ]);
    $result = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $result);
    $message = $result->getMessage();
    verify($message)->stringContainsString('A post notification is stuck');
    verify($message)->stringNotContainsString('Some post notifications are stuck');
  }

  public function testItRendersPluralHeadingForMultipleStuckParents() {
    $this->newslettersRepository->method('findStuckPostNotificationParents')->willReturn([
      ['parent' => $this->makeParent(1, 'Weekly digest'), 'hasInvalid' => false],
      ['parent' => $this->makeParent(2, 'Daily digest'), 'hasInvalid' => true],
    ]);
    $result = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $result);
    $message = $result->getMessage();
    verify($message)->stringContainsString('Some post notifications are stuck');
    verify($message)->stringNotContainsString('A post notification is stuck');
  }

  public function testItRendersPausedAndInvalidReasons() {
    $this->newslettersRepository->method('findStuckPostNotificationParents')->willReturn([
      ['parent' => $this->makeParent(1, 'Paused digest'), 'hasInvalid' => false],
      ['parent' => $this->makeParent(2, 'Invalid digest'), 'hasInvalid' => true],
    ]);
    $result = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $result);
    $message = $result->getMessage();
    verify($message)->stringContainsString('"Paused digest" is paused.');
    verify($message)->stringContainsString('"Invalid digest" is flagged as invalid.');
  }

  public function testItIncludesViewSendingHistoryLinkPerParent() {
    $this->newslettersRepository->method('findStuckPostNotificationParents')->willReturn([
      ['parent' => $this->makeParent(42, 'My digest'), 'hasInvalid' => false],
    ]);
    $result = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $result);
    $message = $result->getMessage();
    verify($message)->stringContainsString('admin.php?page=mailpoet-newsletters#/notification/history/42');
    verify($message)->stringContainsString('View sending history');
  }

  public function testItEscapesParentSubject() {
    $this->newslettersRepository->method('findStuckPostNotificationParents')->willReturn([
      ['parent' => $this->makeParent(1, '<script>alert(1)</script>'), 'hasInvalid' => false],
    ]);
    $result = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $result);
    $message = $result->getMessage();
    verify($message)->stringNotContainsString('<script>alert(1)</script>');
    verify($message)->stringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;');
  }

  public function testItReturnsNullWhenDismissed() {
    $this->newslettersRepository->expects($this->never())->method('findStuckPostNotificationParents');
    $notice = $this->createNotice();
    $notice->disable();

    verify($notice->init(true))->null();
  }

  public function testDisableSetsTransient() {
    $notice = $this->createNotice();
    verify($this->wp->getTransient(StuckPostNotificationNotice::OPTION_NAME))->false();

    $notice->disable();

    verify($this->wp->getTransient(StuckPostNotificationNotice::OPTION_NAME))->true();
  }

  private function createNotice(): StuckPostNotificationNotice {
    return new StuckPostNotificationNotice($this->wp, $this->newslettersRepository);
  }

  private function makeParent(int $id, string $subject): NewsletterEntity {
    $parent = $this->createMock(NewsletterEntity::class);
    $parent->method('getId')->willReturn($id);
    $parent->method('getSubject')->willReturn($subject);
    return $parent;
  }
}
