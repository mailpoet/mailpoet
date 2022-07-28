<?php

namespace MailPoet\DI;

use MailPoet\Config\Env;
use MailPoet\Validator\Validator;
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
    $container->autowire(\MailPoet\AdminPages\Pages\Automation::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\AutomationEditor::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\ExperimentalFeatures::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\FormEditor::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Forms::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Help::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Logs::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\NewsletterEditor::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Newsletters::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\MP2Migration::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Upgrade::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Segments::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Settings::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\Subscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\SubscribersExport::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\SubscribersImport::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\WelcomeWizard::class)->setPublic(true);
    $container->autowire(\MailPoet\AdminPages\Pages\WooCommerceSetup::class)->setPublic(true);
    // Analytics
    $container->autowire(\MailPoet\Analytics\Analytics::class)->setPublic(true);
    $container->autowire(\MailPoet\Analytics\Reporter::class)->setPublic(true);
    // API
    $container->autowire(\MailPoet\API\JSON\API::class)
      ->addArgument(new Reference(ContainerWrapper::class))
      ->setAutowired(true)
      ->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\ErrorHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\API\MP\v1\CustomFields::class)->setPublic(true);
    $container->autowire(\MailPoet\API\MP\v1\API::class)->setPublic(true);
    $container->autowire(\MailPoet\API\MP\v1\Subscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\API\MP\v1\Segments::class)->setPublic(true);
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
    $container->autowire(\MailPoet\API\JSON\v1\SubscriberStats::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Subscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\WoocommerceSettings::class)->setPublic(true);
    $container->autowire(\MailPoet\Util\APIPermissionHelper::class)->setPublic(true);
    // API response builders
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\NewsletterTemplatesResponseBuilder::class);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder::class);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\FormsResponseBuilder::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\DynamicSegmentsResponseBuilder::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\ResponseBuilders\ScheduledTaskSubscriberResponseBuilder::class)->setPublic(true);
    // Automatic emails
    $container->autowire(\MailPoet\AutomaticEmails\AutomaticEmails::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\AutomaticEmailFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\WooCommerce\WooCommerce::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\WooCommerce\WooCommerceEventFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory::class)->setPublic(true);
    $container->autowire(\MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct::class)->setPublic(true);
    // Automation
    $container->autowire(\MailPoet\Automation\Engine\API\API::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\API\EndpointContainer::class)
      ->setPublic(true)
      ->setArgument('$container', new Reference(ContainerWrapper::class));
    $container->autowire(\MailPoet\Automation\Engine\Builder\CreateWorkflowFromTemplateController::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Builder\UpdateWorkflowController::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Control\ActionScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Control\StepRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Control\SubjectLoader::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Control\Steps\ActionStepRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Control\TriggerHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Engine::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Migrations\Migrator::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Registry::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Storage\WorkflowRunStorage::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Storage\WorkflowStorage::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\WordPress::class)->setPublic(true);
    // Automation - API endpoints
    $container->autowire(\MailPoet\Automation\Engine\Endpoints\Workflows\WorkflowsGetEndpoint::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Endpoints\Workflows\WorkflowsPutEndpoint::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Endpoints\Workflows\WorkflowsCreateFromTemplateEndpoint::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Endpoints\System\DatabasePostEndpoint::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Engine\Endpoints\System\DatabaseDeleteEndpoint::class)->setPublic(true);
    // Automation - core integration
    $container->autowire(\MailPoet\Automation\Integrations\Core\Actions\DelayAction::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Integrations\Core\CoreIntegration::class)->setPublic(true);
    // Automation - MailPoet integration
    $container->autowire(\MailPoet\Automation\Integrations\MailPoet\MailPoetIntegration::class)->setPublic(true);
    $container->autowire(\MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject::class)->setPublic(true)->setShared(false);
    $container->autowire(\MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject::class)->setPublic(true)->setShared(false);
    $container->autowire(\MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger::class)->setPublic(true)->setShared(false);
    $container->autowire(\MailPoet\Automation\Integrations\MailPoet\Templates\WorkflowBuilder::class)->setPublic(true)->setShared(false);
    $container->autowire(\MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction::class)->setPublic(true);
    // Config
    $container->autowire(\MailPoet\Config\AccessControl::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Activator::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\AssetsLoader::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Populator::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Changelog::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\DatabaseInitializer::class);
    $container->autowire(\MailPoet\Config\Hooks::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\HooksWooCommerce::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Initializer::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Localizer::class);
    $container->autowire(\MailPoet\Config\Menu::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Migrator::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\MP2Migrator::class);
    $container->autowire(\MailPoet\Config\RendererFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\ServicesChecker::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Router::class)->setPublic(true);
    $container->autowire(\MailPoet\Config\Shortcodes::class)
      ->setShared(false) // Get a new instance each time $container->get() is called, needed for tests
      ->setPublic(true);
    $container->register(\MailPoet\Config\Renderer::class)
      ->setPublic(true)
      ->setFactory([new Reference(\MailPoet\Config\RendererFactory::class), 'getRenderer']);
    $container->autowire(\MailPoet\Config\PersonalDataExporters::class)->setPublic(true);
    // Doctrine
    $container->autowire(\MailPoet\Doctrine\Annotations\AnnotationReaderProvider::class);
    $container->autowire(\MailPoet\Doctrine\ConfigurationFactory::class);
    $container->autowire(\MailPoet\Doctrine\ConnectionFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Doctrine\EntityManagerFactory::class)->setPublic(true);
    $container->autowire(\MailPoetVendor\Doctrine\ORM\Configuration::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\ConfigurationFactory::class), 'createConfiguration']);
    $container->autowire(\MailPoetVendor\Doctrine\DBAL\Connection::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\ConnectionFactory::class), 'createConnection'])
      ->setPublic(true);
    $container->autowire(\MailPoetVendor\Doctrine\ORM\EntityManager::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\EntityManagerFactory::class), 'createEntityManager'])
      ->setPublic(true);
    $container->autowire(\MailPoet\Doctrine\EventListeners\EmojiEncodingListener::class)->setPublic(true);
    $container->autowire(\MailPoet\Doctrine\EventListeners\LastSubscribedAtListener::class)->setPublic(true);
    $container->autowire(\MailPoet\Doctrine\EventListeners\TimestampListener::class)->setPublic(true);
    $container->autowire(\MailPoet\Doctrine\EventListeners\ValidationListener::class);
    $container->autowire(\MailPoet\Doctrine\Validator\ValidatorFactory::class);
    $container->autowire(\MailPoetVendor\Symfony\Component\Validator\Validator\ValidatorInterface::class)
      ->setFactory([new Reference(\MailPoet\Doctrine\Validator\ValidatorFactory::class), 'createValidator']);
    $container->autowire(\MailPoet\PostEditorBlocks\PostEditorBlock::class);
    $container->autowire(\MailPoet\PostEditorBlocks\SubscriptionFormBlock::class);
    $container->autowire(\MailPoet\PostEditorBlocks\WooCommerceBlocksIntegration::class);
    // Cron
    $container->autowire(\MailPoet\Cron\CronHelper::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\CronTrigger::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\CronWorkerRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\CronWorkerScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Daemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\DaemonHttpRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Supervisor::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Triggers\WordPress::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\WorkersFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\SendingThrottlingHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\Scheduler::class);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\StatsNotificationsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\Migration::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer::class)->setPublic(true);
    // Cron workers
    $container->autowire(\MailPoet\Cron\Workers\Scheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\SendingQueue::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\Tasks\Links::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\Worker::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\Bounce::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SendingQueue\Migration::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\WooCommerceSync::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\ExportFilesCleanup::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\Beamer::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscribersEmailCount::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\InactiveSubscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\UnsubscribeTokens::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscriberLinkTokens::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\WooCommercePastOrders::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\ReEngagementEmailsScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscribersEngagementScore::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscribersLastEngagement::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscribersCountCacheRecalculation::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\SubscribersStatsReport::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Workers\NewsletterTemplateThumbnails::class)->setPublic(true);
    // Custom field
    $container->autowire(\MailPoet\CustomFields\ApiDataSanitizer::class);
    $container->autowire(\MailPoet\CustomFields\CustomFieldsRepository::class)->setPublic(true);
    // Features
    $container->autowire(\MailPoet\Features\FeaturesController::class)->setPublic(true);
    $container->autowire(\MailPoet\Features\FeatureFlagsController::class)->setPublic(true);
    $container->autowire(\MailPoet\Features\FeatureFlagsRepository::class)->setPublic(true);
    // Form
    $container->autowire(\MailPoet\Form\Util\FieldNameObfuscator::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\ApiDataSanitizer::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\AssetsController::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\DisplayFormInWPContent::class);
    $container->autowire(\MailPoet\Form\FormsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\Renderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\BlocksRenderer::class);
    $container->autowire(\MailPoet\Form\BlockStylesRenderer::class);
    $container->autowire(\MailPoet\Form\BlockWrapperRenderer::class);
    $container->autowire(\MailPoet\Form\Block\BlockRendererHelper::class);
    $container->autowire(\MailPoet\Form\Block\Column::class);
    $container->autowire(\MailPoet\Form\Block\Columns::class);
    $container->autowire(\MailPoet\Form\Block\Checkbox::class);
    $container->autowire(\MailPoet\Form\Block\Date::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\Block\Divider::class);
    $container->autowire(\MailPoet\Form\Block\Html::class);
    $container->autowire(\MailPoet\Form\Block\Heading::class);
    $container->autowire(\MailPoet\Form\Block\Image::class);
    $container->autowire(\MailPoet\Form\Block\Paragraph::class);
    $container->autowire(\MailPoet\Form\Block\Radio::class);
    $container->autowire(\MailPoet\Form\Block\Segment::class);
    $container->autowire(\MailPoet\Form\Block\Select::class);
    $container->autowire(\MailPoet\Form\Block\Submit::class);
    $container->autowire(\MailPoet\Form\Block\Text::class);
    $container->autowire(\MailPoet\Form\Block\Textarea::class);
    $container->autowire(\MailPoet\Form\FormHtmlSanitizer::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\FormMessageController::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\FormSaveController::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\Listing\FormListingRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Form\PreviewPage::class);
    $container->autowire(\MailPoet\Form\Templates\TemplateRepository::class);
    $container->autowire(\MailPoet\Form\Util\Styles::class);
    $container->autowire(\MailPoet\Form\Util\CustomFonts::class)->setPublic(true);
    // Helpscout
    $container->autowire(\MailPoet\Helpscout\Beacon::class)->setPublic(true);
    // Listing
    $container->autowire(\MailPoet\Listing\Handler::class)->setPublic(true);
    $container->autowire(\MailPoet\Listing\PageLimit::class)->setPublic(true);
    // Logging
    $container->autowire(\MailPoet\Logging\LoggerFactory::class);
    $container->autowire(\MailPoet\Logging\LogRepository::class)->setPublic(true);
    // Notices
    $container->autowire(\MailPoet\Util\Notices\PermanentNotices::class);
    //Referrals
    $container->autowire(\MailPoet\Referrals\ReferralDetector::class);
    // Router
    $container->autowire(\MailPoet\Router\Endpoints\CronDaemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\FormPreview::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Subscription::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\ViewInBrowser::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Track::class)->setPublic(true);
    // Statistics
    $container->autowire(\MailPoet\Statistics\Track\Clicks::class);
    $container->autowire(\MailPoet\Statistics\Track\Opens::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\Track\PageViewCookie::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\Track\SubscriberActivityTracker::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\Track\SubscriberCookie::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\Track\SubscriberHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\Track\WooCommercePurchases::class);
    $container->autowire(\MailPoet\Statistics\Track\Unsubscribes::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\StatisticsFormsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\StatisticsBouncesRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\StatisticsClicksRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\StatisticsOpensRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\StatisticsUnsubscribesRepository::class);
    $container->autowire(\MailPoet\Statistics\StatisticsWooCommercePurchasesRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\UserAgentsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Router::class)
      ->setArgument('$container', new Reference(ContainerWrapper::class));
    // Mailer
    $container->autowire(\MailPoet\Mailer\MailerFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Mailer\WordPress\WordpressMailerReplacer::class);
    $container->autowire(\MailPoet\Mailer\Methods\Common\BlacklistCheck::class);
    $container->autowire(\MailPoet\Mailer\MetaInfo::class);
    $container->autowire(\MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper::class)->setPublic(true);
    // Subscribers
    $container->autowire(\MailPoet\Subscribers\NewSubscriberNotificationMailer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ConfirmationEmailMailer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\RequiredCustomFieldValidator::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberActions::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscribersEmailCountsController::class);
    $container->autowire(\MailPoet\Subscribers\InactiveSubscribersController::class);
    $container->autowire(\MailPoet\Subscribers\LinkTokens::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscribersRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberIPsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberListingRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberSegmentRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberTagRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberCustomFieldRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberPersonalDataEraser::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberSaveController::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\SubscriberSubscribeController::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ImportExport\ImportExportRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewslettersExporter::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewsletterOpensExporter::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewsletterClicksExporter::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository::class);
    $container->autowire(\MailPoet\Subscribers\SubscribersCountsController::class)->setPublic(true);
    // Segments
    $container->autowire(\MailPoet\Segments\WooCommerce::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\WP::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SubscribersFinder::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SegmentsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SegmentSubscribersRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SegmentListingRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SegmentsSimpleListRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SegmentSaveController::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\SegmentDependencyValidator::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\FilterFactory::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\FilterHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\DynamicSegmentFilterRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\EmailAction::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\EmailActionClickAny::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\SubscriberScore::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\SubscriberSegment::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\SubscriberTag::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\UserRole::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceMembership::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\SegmentSaveController::class)->setPublic(true);
    $container->autowire(\MailPoet\Segments\DynamicSegments\FilterDataMapper::class)->setPublic(true);
    // Services
    $container->autowire(\MailPoet\Services\Bridge::class)->setPublic(true);
    $container->autowire(\MailPoet\Services\AuthorizedEmailsController::class)->setPublic(true);
    $container->autowire(\MailPoet\Services\CongratulatoryMssEmailController::class)->setPublic(true);
    // Tasks
    $container->autowire(\MailPoet\Tasks\State::class);
    // Settings
    $container->autowire(\MailPoet\Settings\SettingsController::class)->setPublic(true);
    $container->autowire(\MailPoet\Settings\SettingsChangeHandler::class)->setPublic(true);
    $container->autowire(\MailPoet\Settings\SettingsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Settings\TrackingConfig::class)->setPublic(true);
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
    $container->autowire(\MailPoet\Subscription\ManageSubscriptionFormRenderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\Pages::class)->setPublic(true)
      ->setShared(false); // Get a new instance each time $container->get() is called, needed for tests
    $container->autowire(\MailPoet\Subscription\Registration::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\Throttling::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscription\SubscriptionUrlFactory::class)->setPublic(true);
    // Newsletter
    $container->autowire(\MailPoet\Newsletter\ApiDataSanitizer::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\AutomatedLatestContent::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\NewsletterSaveController::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\NewsletterPostsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\NewslettersRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\AutomaticEmailsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\NewsletterHtmlSanitizer::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Url::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\NewsletterValidator::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Links\Links::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Listing\NewsletterListingRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Options\NewsletterOptionsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Preview\SendPreviewController::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\AbandonedCartContent::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\AutomatedLatestContentBlock::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Button::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Divider::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Footer::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Header::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Image::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Placeholder::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Renderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Social::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Spacer::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Blocks\Text::class);
    $container->autowire(\MailPoet\Newsletter\Renderer\Columns\Renderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Renderer\Preprocessor::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Renderer\Renderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Segment\NewsletterSegmentRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Shortcodes\ShortcodesHelper::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Shortcodes\Shortcodes::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Shortcodes\Categories\Date::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Shortcodes\Categories\Link::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Shortcodes\Categories\Newsletter::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Shortcodes\Categories\Subscriber::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Scheduler\AutomationEmailScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Scheduler\WelcomeScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Scheduler\PostNotificationScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Scheduler\ReEngagementScheduler::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Sending\ScheduledTasksRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Sending\ScheduledTaskSubscribersListingRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\Sending\SendingQueuesRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\ViewInBrowser\ViewInBrowserController::class)->setPublic(true);
    $container->autowire(\MailPoet\Newsletter\ViewInBrowser\ViewInBrowserRenderer::class)->setPublic(true);
    $container->autowire(\MailPoet\Statistics\GATracking::class)->setPublic(true);
    // Newsletter templates
    $container->autowire(\MailPoet\NewsletterTemplates\NewsletterTemplatesRepository::class)->setPublic(true);
    $container->autowire(\MailPoet\NewsletterTemplates\ThumbnailSaver::class)->setPublic(true);
    // Util
    $container->autowire(\MailPoet\Util\Cookies::class);
    $container->autowire(\MailPoet\Util\DBCollationChecker::class);
    $container->autowire(\MailPoet\Util\Url::class)->setPublic(true);
    $container->autowire(\MailPoet\Util\Installation::class);
    $container->autowire(\MailPoet\Util\Security::class);
    $container->autowire(\MailPoet\Util\License\Features\Subscribers::class)->setPublic(true);
    $container->autowire(\MailPoet\Util\License\License::class)->setPublic(true);
    $container->register(\MailPoet\Util\CdnAssetUrl::class)
      ->setPublic(true)
      ->setFactory([__CLASS__, 'getCdnAssetsUrl']);
    $container->autowire(\MailPoet\Newsletter\Scheduler\Scheduler::class)->setPublic(true);
    // Validator
    $container->autowire(Validator::class)->setPublic(true);
    // WooCommerce
    $container->autowire(\MailPoet\WooCommerce\Helper::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\Settings::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\SubscriberEngagement::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\Subscription::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmailHooks::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails\Template::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails\Renderer::class)->setPublic(true);
    $container->autowire(\MailPoet\WooCommerce\TransactionalEmails\ContentPreprocessor::class)->setPublic(true);
    // WordPress
    $container->autowire(\MailPoet\WP\Emoji::class)->setPublic(true);
    $container->autowire(\MailPoet\WP\Functions::class)->setPublic(true);
    $container->autowire(\MailPoet\WP\AutocompletePostListLoader::class)->setPublic(true);
    // Third party classes
    $container->autowire(\MailPoetVendor\CSS::class)->setClass(\MailPoetVendor\CSS::class)->setPublic(true);
    $container->autowire(\MailPoetVendor\csstidy::class)->setClass(\MailPoetVendor\csstidy::class);
    // Cache
    $container->autowire(\MailPoet\Cache\TransientCache::class)->setPublic(true);
    // Tags
    $container->autowire(\MailPoet\Tags\TagRepository::class)->setPublic(true);
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
    if ($container === null) {
      return null;
    }
    if (!$container->has(IContainerConfigurator::PREMIUM_CONTAINER_SERVICE_SLUG)) {
      return null;
    }
    return $container->get(IContainerConfigurator::PREMIUM_CONTAINER_SERVICE_SLUG)->get($id);
  }

  public static function getCdnAssetsUrl(): \MailPoet\Util\CdnAssetUrl {
    return new \MailPoet\Util\CdnAssetUrl((string)Env::$baseUrl);
  }
}
