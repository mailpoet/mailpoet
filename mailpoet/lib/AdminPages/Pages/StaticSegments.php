<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Listing\PageLimit;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class StaticSegments {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    NewslettersRepository $newslettersRepository,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->newslettersRepository = $newslettersRepository;
    $this->wp = $wp;
  }

  /**
   * @return void
   */
  public function render() {
    $this->assetsController->setupDataViewsDependencies();

    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('segments');
    $data['api'] = [
      'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
      'nonce' => $this->wp->wpCreateNonce('wp_rest'),
    ];
    $data['confirmation_emails'] = $this->getConfirmationEmails();
    $data['pages'] = $this->getPages();

    $this->pageRenderer->displayPage('segments/static.html', $data);
  }

  /** @return array<int, array{id: int, subject: string}> */
  private function getConfirmationEmails(): array {
    $newsletters = $this->newslettersRepository->findBy(
      ['type' => NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER, 'deletedAt' => null],
      ['subject' => 'ASC']
    );

    return array_map(function (NewsletterEntity $newsletter) {
      return [
        'id' => (int)$newsletter->getId(),
        'subject' => $newsletter->getSubject() ?: __('(no subject)', 'mailpoet'),
      ];
    }, $newsletters);
  }

  /** @return array<int, array{id: int, title: string}> */
  private function getPages(): array {
    $wpPages = $this->wp->getPosts([
      'post_type' => 'page',
      'post_status' => 'publish',
      'orderby' => 'title',
      'order' => 'ASC',
      'posts_per_page' => -1,
    ]);

    return array_map(function ($page) {
      return [
        'id' => (int)$page->ID,
        'title' => $page->post_title, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ];
    }, $wpPages);
  }
}
