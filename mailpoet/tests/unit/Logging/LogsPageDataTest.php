<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

class LogsPageDataTest extends \MailPoetUnitTest {
  /** @var WPFunctions & MockObject */
  private $wp;

  public function _before() {
    parent::_before();
    $this->wp = $this->createMock(WPFunctions::class);
    $this->wp->method('escUrlRaw')->willReturnArgument(0);
    $this->wp->method('restUrl')->willReturn('https://example.com/wp-json/');
    $this->wp->method('adminUrl')->willReturn('https://example.com/wp-admin/admin-post.php');
    $this->wp->method('wpCreateNonce')->willReturnCallback(fn($action) => 'nonce-' . $action);
  }

  public function testItReturnsExactlyTheKeysTheLogsViewNeeds(): void {
    $data = (new LogsPageData($this->wp))->get();
    $keys = array_keys($data);
    sort($keys);
    verify($keys)->same(['api', 'download', 'logs_default_from']);
  }

  public function testItDefaultsTheFromDateToSevenDaysAgo(): void {
    $data = (new LogsPageData($this->wp))->get();
    verify($data['logs_default_from'])->same((new Carbon())->subDays(7)->format('Y-m-d'));
  }

  public function testItBuildsTheRestApiData(): void {
    $data = (new LogsPageData($this->wp))->get();
    verify($data['api'])->same([
      'root' => 'https://example.com/wp-json',
      'nonce' => 'nonce-wp_rest',
    ]);
  }

  public function testItBuildsTheDownloadData(): void {
    $data = (new LogsPageData($this->wp))->get();
    verify($data['download'])->same([
      'action_url' => 'https://example.com/wp-admin/admin-post.php',
      'nonce' => 'nonce-mailpoet_download_logs_nonce',
    ]);
    verify($data['download']['nonce'])->notSame('nonce-wp_rest');
  }
}
