<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\FormEntity;
use MailPoet\Router\Endpoints\FormPreview;
use MailPoet\Router\Router;
use MailPoet\Test\DataFactories\Form;

class FormPreviewTest extends \MailPoetTest {
  private const SUCCESS_MESSAGE = 'Draft form preview rendered';

  /** @var int */
  private $editorUserId;

  /** @var FormEntity */
  private $disabledForm;

  public function _before() {
    parent::_before();
    // Editors can open the MailPoet admin but cannot manage forms by default
    $this->editorUserId = $this->tester->createWordPressUser('form_preview_editor@example.com', 'editor');
    $this->disabledForm = (new Form())
      ->withName('Draft form')
      ->withStatus(FormEntity::STATUS_DISABLED)
      ->withSuccessMessage(self::SUCCESS_MESSAGE)
      ->create();
  }

  public function testItRejectsAnonymousRequests() {
    wp_set_current_user(0);
    verify($this->routePreviewRequest())->equals(Router::RESPONE_FORBIDDEN);
  }

  public function testItRejectsUsersWhoCannotManageForms() {
    verify(user_can($this->editorUserId, AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->true();
    wp_set_current_user($this->editorUserId);
    verify($this->routePreviewRequest())->equals(Router::RESPONE_FORBIDDEN);
  }

  public function testItRendersDisabledFormForUsersWhoCanManageForms() {
    wp_set_current_user(1); // administrator
    $endpoint = $this->diContainer->get(FormPreview::class);

    verify($this->routePreviewRequest())->null();
    verify(has_filter('the_content', [$endpoint, 'renderContent']))->notFalse();
    verify($endpoint->renderContent())->stringContainsString(self::SUCCESS_MESSAGE);
  }

  private function routePreviewRequest() {
    $routerData = [
      Router::NAME => '',
      'endpoint' => FormPreview::ENDPOINT,
      'action' => FormPreview::ACTION_VIEW,
      'data' => Router::encodeRequestData([
        'id' => $this->disabledForm->getId(),
        'form_type' => FormEntity::DISPLAY_TYPE_OTHERS,
        'editor_url' => 'http://example.com/editor',
      ]),
    ];
    $router = Stub::construct(
      Router::class,
      [new AccessControl(), $this->diContainer, $routerData],
      [
        'terminateRequest' => function($code, $message) {
          return $code;
        },
      ]
    );
    return $router->init();
  }
}
