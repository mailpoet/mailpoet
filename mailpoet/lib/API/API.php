<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API;

use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class API {
  /**
   * @param string $version
   * @return \MailPoet\API\MP\v1\API
   * @throws \Exception
   */
  public static function MP($version) {
    /** @var class-string<\MailPoet\API\MP\v1\API> $apiClass */
    $apiClass = sprintf('%s\MP\%s\API', __NAMESPACE__, $version);
    $container = ContainerWrapper::getInstance();
    self::ensureReady($container);
    try {
      return $container->get($apiClass);
    } catch (ServiceNotFoundException $e) {
      throw new \Exception(__('Invalid API version.', 'mailpoet'));
    }
  }

  /**
   * Guards against API calls that hit the database before MailPoet has finished
   * its activator/migration path on `init`. Without this, an early call (e.g. from
   * `plugins_loaded` during a plugin upgrade) could query Doctrine entities against
   * an outdated schema and fatal mid-request.
   */
  private static function ensureReady(ContainerWrapper $container): void {
    try {
      $currentDbVersion = $container->get(SettingsController::class)->get('db_version');
    } catch (\Throwable $e) {
      $currentDbVersion = null;
    }

    if (version_compare((string)$currentDbVersion, Env::$version) === 0) {
      return;
    }

    throw new \Exception(__('MailPoet is still initializing or upgrading. Call the public API from the "init" hook (default priority) or later.', 'mailpoet'));
  }
}
