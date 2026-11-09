<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetDeleteEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetDuplicateEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetPreviewEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetPutEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetRestoreEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetsGetEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetsPostEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints\EmailPresetTrashEndpoint;
use MailPoet\WP\Functions as WPFunctions;

class Api {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function initialize(): void {
    $this->wp->addAction(RestApi::REST_API_INIT_ACTION, [$this, 'registerRoutes']);
  }

  public function registerRoutes(RestApi $api): void {
    $api->registerGetRoute('email-presets', EmailPresetsGetEndpoint::class);
    $api->registerPostRoute('email-presets', EmailPresetsPostEndpoint::class);
    $api->registerPutRoute('email-presets/(?P<id>\d+)', EmailPresetPutEndpoint::class);
    $api->registerDeleteRoute('email-presets/(?P<id>\d+)', EmailPresetDeleteEndpoint::class);
    $api->registerPostRoute('email-presets/(?P<id>\d+)/duplicate', EmailPresetDuplicateEndpoint::class);
    $api->registerPostRoute('email-presets/(?P<id>\d+)/trash', EmailPresetTrashEndpoint::class);
    $api->registerPostRoute('email-presets/(?P<id>\d+)/restore', EmailPresetRestoreEndpoint::class);
    $api->registerGetRoute('email-presets/(?P<id>\d+)/preview', EmailPresetPreviewEndpoint::class);
  }
}
