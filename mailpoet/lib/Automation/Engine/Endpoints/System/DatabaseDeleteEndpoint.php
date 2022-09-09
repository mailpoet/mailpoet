<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\System;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;

class DatabaseDeleteEndpoint extends Endpoint {
  /** @var FeatureFlagsController */
  private $featureFlagsController;

  /** @var Migrator */
  private $migrator;

  public function __construct(
    FeatureFlagsController $featureFlagsController,
    Migrator $migrator
  ) {
    $this->migrator = $migrator;
    $this->featureFlagsController = $featureFlagsController;
  }

  public function handle(Request $request): Response {
    $this->migrator->deleteSchema();
    $this->featureFlagsController->set(FeaturesController::AUTOMATION, false);
    return new Response(null);
  }
}
