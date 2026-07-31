<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Form\Block;
use MailPoet\Listing\PageLimit;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class SubscribersTest extends \MailPoetUnitTest {
  /** @var PageRenderer & MockObject */
  private $pageRenderer;

  /** @var SettingsController & MockObject */
  private $settings;

  /** @var Subscribers */
  private $page;

  public function _before() {
    parent::_before();

    $this->pageRenderer = $this->createMock(PageRenderer::class);
    $this->settings = $this->createMock(SettingsController::class);

    $customFieldsRepository = $this->createMock(CustomFieldsRepository::class);
    $customFieldsRepository->method('findAllActive')->willReturn([]);

    $segmentsListRepository = $this->createMock(SegmentsSimpleListRepository::class);
    $segmentsListRepository->method('getListWithSubscribedSubscribersCounts')->willReturn([]);

    $dateBlock = $this->createMock(Block\Date::class);
    $dateBlock->method('getDateFormats')->willReturn([]);
    $dateBlock->method('getMonthNames')->willReturn([]);

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('escUrlRaw')->willReturnArgument(0);
    $wp->method('restUrl')->willReturn('https://example.com/wp-json/');
    $wp->method('wpCreateNonce')->willReturn('nonce');

    $this->page = new Subscribers(
      $this->pageRenderer,
      $this->createMock(AssetsController::class),
      $this->createMock(PageLimit::class),
      $dateBlock,
      $segmentsListRepository,
      $customFieldsRepository,
      $this->createMock(CustomFieldsResponseBuilder::class),
      $this->settings,
      $wp
    );
  }

  public function testItPassesTimeZoneListWhenCollectionIsEnabled() {
    $data = $this->renderWithTimeZoneCollection(true);

    $this->assertTrue($data['collect_subscriber_timezones']);
    $this->assertContains('Europe/Prague', $data['timezone_list']);
  }

  public function testItPassesNoTimeZoneListWhenCollectionIsDisabled() {
    $data = $this->renderWithTimeZoneCollection(false);

    $this->assertFalse($data['collect_subscriber_timezones']);
    $this->assertSame([], $data['timezone_list']);
  }

  private function renderWithTimeZoneCollection(bool $enabled): array {
    $this->settings
      ->method('isSettingEnabled')
      ->with('collect_subscriber_timezones.enabled')
      ->willReturn($enabled);

    $data = [];
    $this->pageRenderer
      ->expects($this->once())
      ->method('displayPage')
      ->willReturnCallback(function (string $template, array $renderedData) use (&$data): void {
        $data = $renderedData;
      });

    $this->page->render();

    return $data;
  }
}
