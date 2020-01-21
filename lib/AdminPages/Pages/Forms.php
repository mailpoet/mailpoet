<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Segment;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Util\Installation;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;

class Forms {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var Installation */
  private $installation;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    Installation $installation,
    SettingsController $settings,
    UserFlagsController $userFlags,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->installation = $installation;
    $this->userFlags = $userFlags;
    $this->wp = $wp;
    $this->settings = $settings;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();
    $data['is_new_user'] = $this->installation->isNewInstallation();

    $data = $this->getNPSSurveyData($data);

    $this->pageRenderer->displayPage('forms.html', $data);
  }

  public function getNPSSurveyData($data) {
    $data['display_nps_survey'] = false;
    if ($this->userFlags->get('display_new_form_editor_nps_survey')) {
      $data['current_wp_user'] = $this->wp->wpGetCurrentUser()->to_array();
      $data['site_url'] = $this->wp->siteUrl();
      $data['premium_plugin_active'] = License::getLicense();
      $data['current_wp_user_firstname'] = $this->wp->wpGetCurrentUser()->user_firstname;
      $installedAtDateTime = new \DateTime($this->settings->get('installed_at'));
      $data['installed_days_ago'] = (int)$installedAtDateTime->diff(new \DateTime())->format('%a');

      $data['display_nps_survey'] = true;
      $this->userFlags->set('display_new_form_editor_nps_survey', false);
    }
    return $data;
  }
}
