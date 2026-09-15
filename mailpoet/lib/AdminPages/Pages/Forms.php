<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class Forms {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var WPFunctions */
  private $wp;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentsResponseBuilder */
  private $segmentsResponseBuilder;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    UserFlagsController $userFlags,
    SegmentsRepository $segmentsRepository,
    SegmentsResponseBuilder $segmentsResponseBuilder,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->userFlags = $userFlags;
    $this->wp = $wp;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
  }

  public function render() {
    $this->assetsController->setupDataViewsDependencies();

    $data = [];
    $data['segments'] = $this->segmentsResponseBuilder->buildForListing($this->segmentsRepository->findAll());
    $data['api'] = [
      'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
      'nonce' => $this->wp->wpCreateNonce('wp_rest'),
    ];

    $data = $this->getNPSSurveyData($data);

    $this->pageRenderer->displayPage('forms.html', $data);
  }

  public function getNPSSurveyData($data) {
    $data['display_nps_survey'] = false;
    if ($this->userFlags->get('display_new_form_editor_nps_survey')) {
      $data['current_wp_user'] = $this->wp->wpGetCurrentUser()->to_array();
      $data['current_wp_user_firstname'] = $this->wp->wpGetCurrentUser()->user_firstname;
      $data['display_nps_survey'] = true;
      $this->userFlags->set('display_new_form_editor_nps_survey', false);
    }
    return $data;
  }
}
