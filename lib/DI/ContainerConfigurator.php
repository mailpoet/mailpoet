<?php

namespace MailPoet\DI;

use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoetVendor\Symfony\Component\DependencyInjection\Reference;

class ContainerConfigurator implements IContainerConfigurator {
  public function getDumpNamespace() {
    return 'MailPoetGenerated';
  }

  public function getDumpClassname() {
    return 'FreeCachedContainer';
  }

  public function configure(ContainerBuilder $container) {
    // Every service must be registered
    // Strict mode disables magical loading services by looking for an instance within other services
    // see https://symfonycasts.com/screencast/symfony4-upgrade/service-deprecations#strict-autowiring-mode
    $container->setParameter('container.autowiring.strict_mode', true);

    // Premium plugin services factory
    $container->register(IContainerConfigurator::PREMIUM_CONTAINER_SERVICE_SLUG)
      ->setSynthetic(true)
      ->setPublic(true);
    // Container wrapper service
    $container->register(ContainerWrapper::class)
      ->setPublic(true)
      ->setFactory([
      ContainerWrapper::class,
      'getInstance',
      ]);
    // AdminPages
    $container->autowire(\MailPoet\AdminPages\PageRenderer::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\DynamicSegments::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\ExperimentalFeatures::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\FormEditor::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Forms::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Help::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\NewsletterEditor::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Newsletters::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\MP2Migration::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Premium::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\RevenueTrackingPermission::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Segments::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Settings::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Subscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\SubscribersAPIKeyInvalid::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\SubscribersExport::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\SubscribersImport::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Update::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\WelcomeWizard::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\WooCommerceListImport::class)->setPublic(true);
    // Analytics
    $container->autowire(\MailPoet\Analytics\Analytics::class)->setPublic(true);
    $container->autowire(\MailPoet\Analytics\Reporter::class)->setPublic(true);
    // API
    $container->autowire(\MailPoet\API\JSON\API::class)
      ->addArgument(new Reference(ContainerWrapper::class))
      ->setAutowired(true)
      ->setPublic(true);
    $container->autowire(\MailPoet\API\MP\v1\API::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Analytics::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\AutomatedLatestContent::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\AutomaticEmails::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\CustomFields::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\DynamicSegments::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\FeatureFlags::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Forms::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\ImportExport::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Mailer::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\MP2Migrator::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Newsletters::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\NewsletterLinks::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\NewsletterTemplates::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Premium::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Segments::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\SendingQueue::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Services::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Settings::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\UserFlags::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\SendingTaskSubscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Setup::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Subscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\WoocommerceSettings::class)->setPublic(true);
    // API response builders
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder::class);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder::class);
    // Config
    $container->autowire(\MailPoet\Config\AccessControl::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Activator::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Populator::class);
    $container->autowire(\MailPoet\Config\Changelog::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\DatabaseInitializer::class);
    $container->autowire(\MailPoet\Config\Hooks::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Initializer::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Menu::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\MP2Migrator::class);
    $container->autowire(\MailPoet\Config\RendererFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\ServicesChecker::class);
    $container->autowire(\MailPoet\Config\Shortcodes::class)
      ->setShared(false); // Get a new instance each time $container->get() is called, needed for tests
    $container->register(\MailPoet\Config\Renderer::class)
      ->setPublic(true)
      ->setFactory([new Reference(\MailPoet\Config\RendererFactory::class), 'getRenderer']);
    // Doctrine
    $container->autowire(\MailPoet\Doctrine\Annotations\AnnotationReaderProvider::class);
    $container->autowire(\MailPoet\Doctrine\ConfigurationFactory::class);
    $container->autowire(\MailPoet\Doctrine\ConnectionFactory::class);
    $container->autowire(\MailPoet\Doctrine\EntityManagerFactory::class);
    $container->autowire(\MailPoetVendor\Doctrine\ORM\Configuration::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\ConfigurationFactory::class), 'createConfiguration']);
    $container->autowire(\MailPoetVendor\Doctrine\DBAL\Connection::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\ConnectionFactory::class), 'createConnection'])
      ->setPublic(true);
    $container->autowire(\MailPoetVendor\Doctrine\ORM\EntityManager::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\EntityManagerFactory::class), 'createEntityManager'])
      ->setPublic(true);
    $container->autowire(\MailPoet\Doctrine\EventListeners\TimestampListener::class);
    $container->autowire(\MailPoet\Doctrine\EventListeners\ValidationListener::class);
    $container->autowire(\MailPoet\Doctrine\Validator\ValidatorFactory::class);
    $container->autowire(\MailPoetVendor\Symfony\Component\Validator\Validator\ValidatorInterface::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\Validator\ValidatorFactory::class), 'createValidator']);
    $container->autowire(\MailPoet\PostEditorBlocks\PostEditorBlock::class);
    $container->autowire(\MailPoet\PostEditorBlocks\SubscriptionFormBlock::class);
    // Dynamic segments
    $container->autowire(\MailPoet\DynamicSegments\DynamicSegmentHooks::class);
    // Cron
    $container->autowire(\MailPoet\Cron\CronHelper::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\CronTrigger::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\CronWorkerRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\CronWorkerScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Daemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\DaemonHttpRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Supervisor::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Triggers\MailPoet::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Triggers\WordPress::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\WorkersFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\Scheduler::class);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\StatsNotificationsRepository::class);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository::class);
    // Cron workers
    $container->autowire(\MailPoet\Cron\Workers\Scheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\SendingQueue::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\Worker::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\Bounce::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\Migration::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\WooCommerceSync::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\ExportFilesCleanup::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\Beamer::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\InactiveSubscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\UnsubscribeTokens::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscriberLinkTokens::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\WooCommercePastOrders::class)->setPublic(true);
    // Custom field
    $container->autowire(\MailPoet\CustomFields\ApiDataSanitizer::class);
    $container->autowire(\MailPoet\CustomFields\CustomFieldsRepository::class);
    // Features
    $container->autowire(\MailPoet\Features\FeaturesController::class);
    $container->autowire(\MailPoet\Features\FeatureFlagsController::class)->setPublic(true);
    $container->autowire(\MailPoet\Features\FeatureFlagsRepository::class)->setPublic(true);
    // Form
    $container->autowire(\MailPoet\Form\Util\FieldNameObfuscator::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\AssetsController::class);
    $container->autowire(\MailPoet\Form\DisplayFormInWPContent::class);
    $container->autowire(\MailPoet\Form\FormsRepository::class);
    $container->autowire(\MailPoet\Form\Renderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\BlocksRenderer::class);
    $container->autowire(\MailPoet\Form\Block\BlockRendererHelper::class);
    $container->autowire(\MailPoet\Form\Block\Checkbox::class);
    $container->autowire(\MailPoet\Form\Block\Date::class);
    $container->autowire(\MailPoet\Form\Block\Divider::class);
    $container->autowire(\MailPoet\Form\Block\Html::class);
    $container->autowire(\MailPoet\Form\Block\Radio::class);
    $container->autowire(\MailPoet\Form\Block\Segment::class);
    $container->autowire(\MailPoet\Form\Block\Select::class);
    $container->autowire(\MailPoet\Form\Block\Submit::class);
    $container->autowire(\MailPoet\Form\Block\Text::class);
    $container->autowire(\MailPoet\Form\Block\Textarea::class);
    $container->autowire(\MailPoet\Form\FormFactory::class);
    $container->autowire(\MailPoet\Form\Util\Styles::class);
    // Helpscout
    $container->autowire(\MailPoet\Helpscout\Beacon::class);
    // Listing
    $container->autowire(\MailPoet\Listing\BulkActionController::class)->setPublic(true);
    $container->autowire(\MailPoet\Listing\BulkActionFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Listing\Handler::class)->setPublic(true);
    $container->autowire(\MailPoet\Listing\PageLimit::class)->setPublic(true);
    // Logging
    $container->autowire(\MailPoet\Logging\LoggerFactory::class);
    // Notices
    $container->autowire(\MailPoet\Util\Notices\PermanentNotices::class);
    //Referrals
    $container->autowire(\MailPoet\Referrals\ReferralDetector::class);
    // Router
    $container->autowire(\MailPoet\Router\Endpoints\CronDaemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Subscription::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\ViewInBrowser::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Track::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\Track\Clicks::class);
    $container->autowire(\MailPoet\Statistics\Track\Opens::class);
    $container->autowire(\MailPoet\Statistics\Track\WooCommercePurchases::class);
    $container->autowire(\MailPoet\Router\Router::class)
      ->setArgument('$container', new Reference(ContainerWrapper::class));
    // Mailer
    $container->autowire(\MailPoet\Mailer\Mailer::class);
    $container->autowire(\MailPoet\Mailer\WordPress\WordpressMailerReplacer::class);
    $container->autowire(\MailPoet\Mailer\Methods\Common\BlacklistCheck::class);
    $container->autowire(\MailPoet\Mailer\MetaInfo::class);
    // Subscribers
    $container->autowire(\MailPoet\Subscribers\NewSubscriberNotificationMailer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ConfirmationEmailMailer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\RequiredCustomFieldValidator::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberActions::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\InactiveSubscribersController::class);
    $container->autowire(\MailPoet\Subscribers\LinkTokens::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscribersRepository::class);
    // Segments
    $container->autowire(\MailPoet\Segments\SubscribersListings::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\WooCommerce::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SubscribersFinder::class);
    // Services
    $container->autowire(\MailPoet\Services\Bridge::class)->setPublic(true);
    $container->autowire(\MailPoet\Services\AuthorizedEmailsController::class);
    $container->autowire(\MailPoet\Services\SPFCheck::class)->setPublic(true);
    // Tasks
    $container->autowire(\MailPoet\Tasks\State::class);
    // Settings
    $container->autowire(\MailPoet\Settings\SettingsController::class)->setPublic(true);
    $container->autowire(\MailPoet\Settings\SettingsRepository::class)->setPublic(true);
    // User Flags
    $container->autowire(\MailPoet\Settings\UserFlagsController::class);
    $container->autowire(\MailPoet\Settings\UserFlagsRepository::class)->setPublic(true);
    // Subscription
    $container->autowire(\MailPoet\Subscription\Captcha::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\CaptchaRenderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\CaptchaSession::class);
    $container->autowire(\MailPoet\Subscription\Comment::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\Form::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\Manage::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\Pages::class)->setPublic(true)
      ->setShared(false); // Get a new instance each time $container->get() is called, needed for tests
    $container->autowire(\MailPoet\Subscription\Registration::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\SubscriptionUrlFactory::class);
    // Newsletter
    $container->autowire(\MailPoet\Newsletter\AutomatedLatestContent::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\NewslettersRepository::class);
    $container->autowire(\MailPoet\Newsletter\Listing\NewsletterListingRepository::class);
    $container->autowire(\MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository::class);
    $container->autowire(\MailPoet\Newsletter\Scheduler\WelcomeScheduler::class);
    $container->autowire(\MailPoet\Newsletter\Scheduler\PostNotificationScheduler::class);
    $container->autowire(\MailPoet\Newsletter\ViewInBrowser\ViewInBrowserController::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\ViewInBrowser\ViewInBrowserRenderer::class)->setPublic(true);
    // Util
    $container->autowire(\MailPoet\Util\Cookies::class);
    $container->autowire(\MailPoet\Util\Url::class)->setPublic(true);
    $container->autowire(\MailPoet\Util\Installation::class);
    $container->autowire(\MailPoet\Util\License\Features\Subscribers::class);
    // WooCommerce
    $container->autowire(\MailPoet\WooCommerce\Helper::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\Settings::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\Subscription::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails\Template::class);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails\Renderer::class);
    // WordPress
    $container->autowire(\MailPoet\WP\Emoji::class)->setPublic(true);
    $container->autowire(\MailPoet\WP\Functions::class)->setPublic(true);
    // Third party classes
    $container->autowire(\MailPoetVendor\csstidy::class)->setClass(\MailPoetVendor\csstidy::class);

    return $container;
  }

  /**
   * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements
   */
  private function registerPremiumService(ContainerBuilder $container, $id) {
    $container->register($id)
      ->setPublic(true)
      ->addArgument($id)
      ->addArgument(new Reference('service_container'))
      ->setFactory([
        self::class,
        'getPremiumService',
      ]);
  }

  public static function getPremiumService($id, ContainerInterface $container = null) {
    if (!$container->has(IContainerConfigurator::PREMIUM_CONTAINER_SERVICE_SLUG)) {
      return null;
    }
    return $container->get(IContainerConfigurator::PREMIUM_CONTAINER_SERVICE_SLUG)->get($id);
  }
}
