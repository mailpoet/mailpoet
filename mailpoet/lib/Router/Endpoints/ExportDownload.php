<?php declare(strict_types = 1);

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Config\Env;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Router\Router;
use MailPoet\Subscribers\ImportExport\Export\Export;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class ExportDownload {
  public const ENDPOINT = 'export_download';
  public const ACTION_SUBSCRIBER_EXPORT = 'subscriberExport';
  public const ACTION_STATISTICS_EXPORT = 'statisticsExport';

  private const DOWNLOAD_TOKEN_LENGTH = 32;
  private const DOWNLOAD_TOKEN_CHARACTERS = 'abcdefghijklmnopqrstuvwxyz0123456789';
  private const EXPORT_DIRECTORY = 'exports';
  private const CONTENT_TYPES = [
    'csv' => 'text/csv; charset=utf-8',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  ];

  /** @var string[] */
  public $allowedActions = [
    self::ACTION_SUBSCRIBER_EXPORT,
    self::ACTION_STATISTICS_EXPORT,
  ];

  public $permissions = [
    'actions' => [
      self::ACTION_SUBSCRIBER_EXPORT => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      self::ACTION_STATISTICS_EXPORT => AccessControl::PERMISSION_MANAGE_EMAILS,
    ],
  ];

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public static function buildSubscriberExportUrl(string $token, string $format, ?string $baseUrl = null): string {
    return self::buildExportUrl(self::ACTION_SUBSCRIBER_EXPORT, $token, $format, $baseUrl);
  }

  public static function buildStatisticsExportUrl(string $token, string $format, ?string $baseUrl = null): string {
    return self::buildExportUrl(self::ACTION_STATISTICS_EXPORT, $token, $format, $baseUrl);
  }

  public static function getExportDirectory(): string {
    return Env::$tempPath . '/' . self::EXPORT_DIRECTORY;
  }

  /**
   * @return array{path: string, token: string}
   */
  public static function createExportFile(string $filePrefix, string $format): array {
    $token = self::generateDownloadToken();
    return [
      'path' => self::getFilePathForToken($filePrefix, $token, $format),
      'token' => $token,
    ];
  }

  public static function generateDownloadToken(): string {
    return Security::generateRandomString(self::DOWNLOAD_TOKEN_LENGTH);
  }

  public static function getFilePathForToken(string $filePrefix, string $token, string $format): string {
    return self::getExportDirectory() . '/' . self::getFileNameForToken($filePrefix, $token, $format);
  }

  public static function ensureExportDirectory(?WPFunctions $wp = null): void {
    $wp = $wp ?? WPFunctions::get();
    $exportDirectory = self::getExportDirectory();
    if (!is_dir($exportDirectory) && !$wp->wpMkdirP($exportDirectory)) {
      throw new \RuntimeException('Could not create the export directory.');
    }
    if (!is_dir($exportDirectory)) {
      throw new \RuntimeException('Could not create the export directory.');
    }
    if (!self::writeFile($exportDirectory . '/index.php', str_replace('\n', PHP_EOL, '<?php\n\n// Silence is golden'))) {
      throw new \RuntimeException('Could not protect the export directory.');
    }
    $htaccessWritten = self::writeFile(
      $exportDirectory . '/.htaccess',
      implode(PHP_EOL, [
        '<IfModule mod_authz_core.c>',
        'Require all denied',
        '</IfModule>',
        '<IfModule !mod_authz_core.c>',
        'Deny from all',
        '</IfModule>',
        '',
      ])
    );
    if (!$htaccessWritten) {
      throw new \RuntimeException('Could not protect the export directory.');
    }
  }

  public function subscriberExport(array $data): void {
    $this->downloadFile($data, Export::getFilePrefix());
  }

  public function statisticsExport(array $data): void {
    $this->downloadFile($data, StatisticsExporter::FILE_PREFIX);
  }

  public function getDownloadFilePath(array $data, string $filePrefix): ?string {
    if (empty($data['token']) || !is_string($data['token'])) {
      return null;
    }

    $token = $data['token'];
    if (
      strlen($token) !== self::DOWNLOAD_TOKEN_LENGTH
      || strspn($token, self::DOWNLOAD_TOKEN_CHARACTERS) !== self::DOWNLOAD_TOKEN_LENGTH
    ) {
      return null;
    }

    if (empty($data['format']) || !is_string($data['format'])) {
      return null;
    }

    $extension = strtolower($data['format']);
    if (!isset(self::CONTENT_TYPES[$extension])) {
      return null;
    }

    $realExportPath = realpath(self::getExportDirectory());
    $filePath = self::getFilePathForToken($filePrefix, $token, $extension);
    $realFilePath = is_file($filePath) ? realpath($filePath) : false;
    if (!is_string($realExportPath) || !is_string($realFilePath)) {
      return null;
    }

    $exportPath = rtrim($realExportPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($realFilePath, $exportPath) !== 0) {
      return null;
    }

    return $realFilePath;
  }

  private static function buildExportUrl(string $action, string $token, string $format, ?string $baseUrl): string {
    $baseUrl = $baseUrl ?? WPFunctions::get()->homeUrl();
    $params = [
      Router::NAME => '',
      'endpoint' => self::ENDPOINT,
      'action' => Helpers::camelCaseToUnderscore($action),
      'data' => Router::encodeRequestData([
        'token' => $token,
        'format' => strtolower($format),
      ]),
    ];
    $separator = strpos($baseUrl, '?') === false ? '?' : '&';
    return $baseUrl . $separator . http_build_query($params, '', '&');
  }

  private static function getFileNameForToken(string $filePrefix, string $token, string $format): string {
    return sprintf(
      '%s%s.%s',
      $filePrefix,
      hash_hmac('sha256', $token, self::getDownloadSecret()),
      strtolower($format)
    );
  }

  private static function getDownloadSecret(): string {
    $secret = '';
    $secretConstants = [
      'AUTH_KEY',
      'SECURE_AUTH_KEY',
      'LOGGED_IN_KEY',
      'NONCE_KEY',
      'AUTH_SALT',
      'SECURE_AUTH_SALT',
      'LOGGED_IN_SALT',
      'NONCE_SALT',
    ];
    foreach ($secretConstants as $constant) {
      if (defined($constant)) {
        $secret .= (string)constant($constant);
      }
    }
    return $secret !== '' ? $secret : (string)Env::$path;
  }

  private static function writeFile(string $filePath, string $contents): bool {
    // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads protection files in MailPoet's export directory.
    if (is_file($filePath) && file_get_contents($filePath) === $contents) {
      return true;
    }
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Writes protection files to MailPoet's export directory.
    return file_put_contents($filePath, $contents) !== false;
  }

  private function downloadFile(array $data, string $filePrefix): void {
    $filePath = $this->getDownloadFilePath($data, $filePrefix);
    if (!$filePath) {
      $this->wp->statusHeader(404);
      exit;
    }

    $extension = strtolower((string)pathinfo($filePath, PATHINFO_EXTENSION));
    if (!$this->wp->headersSent()) {
      header('Content-Type: ' . self::CONTENT_TYPES[$extension]);
      header('Content-Disposition: attachment; filename="' . rtrim($filePrefix, '_') . '.' . $extension . '"');
      header('X-Content-Type-Options: nosniff');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
      $fileSize = filesize($filePath);
      if ($fileSize !== false) {
        header('Content-Length: ' . $fileSize);
      }
    }

    // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads a validated local export file from Env::$tempPath.
    readfile($filePath);
    exit;
  }
}
