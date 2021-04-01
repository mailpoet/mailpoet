<?php

namespace MailPoet\API\JSON\v1;

use Exception;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\FormsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\ApiDataSanitizer;
use MailPoet\Form\DisplayFormInWPContent;
use MailPoet\Form\FormFactory;
use MailPoet\Form\FormSaveController;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Listing\FormListingRepository;
use MailPoet\Form\PreviewPage;
use MailPoet\Form\Util;
use MailPoet\Listing;
use MailPoet\Models\Form;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;

class Forms extends APIEndpoint {


  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
  ];

  /** @var Listing\BulkActionController */
  private $bulkAction;

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var FormFactory */
  private $formFactory;

  /** @var FormsResponseBuilder */
  private $formsResponseBuilder;

  /** @var WPFunctions */
  private $wp;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var FormListingRepository */
  private $formListingRepository;

  /** @var Emoji */
  private $emoji;

  /** @var ApiDataSanitizer */
  private $dataSanitizer;

  /** @var FormSaveController */
  private $formSaveController;

  public function __construct(
    Listing\BulkActionController $bulkAction,
    Listing\Handler $listingHandler,
    UserFlagsController $userFlags,
    FormFactory $formFactory,
    FormsRepository $formsRepository,
    FormListingRepository $formListingRepository,
    FormsResponseBuilder $formsResponseBuilder,
    WPFunctions $wp,
    Emoji $emoji,
    ApiDataSanitizer $dataSanitizer,
    FormSaveController $formSaveController
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $listingHandler;
    $this->userFlags = $userFlags;
    $this->formFactory = $formFactory;
    $this->wp = $wp;
    $this->formsRepository = $formsRepository;
    $this->formListingRepository = $formListingRepository;
    $this->formsResponseBuilder = $formsResponseBuilder;
    $this->emoji = $emoji;
    $this->dataSanitizer = $dataSanitizer;
    $this->formSaveController = $formSaveController;
  }

  public function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = $this->formsRepository->findOneById($id);
    if ($form instanceof FormEntity) {
      return $this->successResponse($this->formsResponseBuilder->build($form));
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
    ]);
  }

  public function setStatus($data = []) {
    $status = (isset($data['status']) ? $data['status'] : null);

    if (!$status) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('You need to specify a status.', 'mailpoet'),
      ]);
    }

    $id = (isset($data['id'])) ? (int)$data['id'] : false;
    $form = $this->formsRepository->findOneById($id);

    if (!$form instanceof FormEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet'),
      ]);
    }

    if (!in_array($status, [FormEntity::STATUS_ENABLED, FormEntity::STATUS_DISABLED])) {
      return $this->badRequest([
        APIError::BAD_REQUEST  =>
          sprintf(
            __('Invalid status. Allowed values are (%1$s), you specified %2$s', 'mailpoet'),
            join(', ', [FormEntity::STATUS_ENABLED, FormEntity::STATUS_DISABLED]),
            $status
          ),
      ]);
    }

    $form->setStatus($status);
    $this->formsRepository->flush();

    if ($status === FormEntity::STATUS_ENABLED) {
      $this->wp->deleteTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);
    }

    $form = $this->formsRepository->findOneById($id);
    if (!$form instanceof FormEntity) return $this->errorResponse();
    return $this->successResponse(
      $form->toArray()
    );
  }

  public function listing($data = []) {
    $data['sort_order'] = $data['sort_order'] ?? 'desc';
    $data['sort_by'] = $data['sort_by'] ?? 'updatedAt';

    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->formListingRepository->getData($definition);
    $count = $this->formListingRepository->getCount($definition);
    $filters = $this->formListingRepository->getFilters($definition);
    $groups = $this->formListingRepository->getGroups($definition);

    return $this->successResponse($this->formsResponseBuilder->buildForListing($items), [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
    ]);
  }

  public function create($data = []) {
    if (isset($data['template-id'])) {
      $formEntity = $this->formFactory->createFormFromTemplate($data['template-id']);
    } else {
      $formEntity = $this->formFactory->createEmptyForm();
    }

    $form = Form::findOne($formEntity->getId());
    if(!$form instanceof Form) return $this->errorResponse();
    return $this->successResponse($form->asArray());
  }

  public function previewEditor($data = []) {
    $formId = $data['id'] ?? null;
    if (!$formId) {
      $this->badRequest();
    }
    $this->wp->setTransient(PreviewPage::PREVIEW_DATA_TRANSIENT_PREFIX . $formId, $data, PreviewPage::PREVIEW_DATA_EXPIRATION);
    return $this->successResponse();
  }

  public function exportsEditor($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $exports = Util\Export::getAll($form->asArray());
      return $this->successResponse($exports);
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
    ]);
  }

  public function saveEditor($data = []) {
    $formId = (isset($data['id']) ? (int)$data['id'] : 0);
    $name = (isset($data['name']) ? $data['name'] : WPFunctions::get()->__('New form', 'mailpoet'));
    $body = (isset($data['body']) ? $data['body'] : []);
    $body = $this->dataSanitizer->sanitizeBody($body);
    $settings = (isset($data['settings']) ? $data['settings'] : []);
    $styles = (isset($data['styles']) ? $data['styles'] : '');
    $status = (isset($data['status']) ? $data['status'] : FormEntity::STATUS_ENABLED);

    // check if the form is used as a widget
    $isWidget = false;
    $widgets = WPFunctions::get()->getOption('widget_mailpoet_form');
    if (!empty($widgets)) {
      foreach ($widgets as $widget) {
        if (isset($widget['form']) && (int)$widget['form'] === $formId) {
          $isWidget = true;
          break;
        }
      }
    }

    // Reset no form cache
    $this->wp->deleteTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);

    // check if the user gets to pick his own lists
    // or if it's selected by the admin
    $formEntity = new FormEntity($name);
    $formEntity->setBody($body);
    $listSelection = $formEntity->getSegmentBlocksSegmentIds();

    // check list selection
    if (count($listSelection)) {
      $settings['segments_selected_by'] = 'user';
      $settings['segments'] = $listSelection;
    } else {
      $settings['segments_selected_by'] = 'admin';
    }

    // Check Custom HTML block permissions
    $customHtmlBlocks = $formEntity->getBlocksByType(FormEntity::HTML_BLOCK_TYPE);
    if (count($customHtmlBlocks) && !$this->wp->currentUserCan('administrator')) {
      return $this->errorResponse([
        Error::FORBIDDEN => __('Only administrator can edit forms containing Custom HTML block.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    if ($body !== null) {
      $body = $this->emoji->sanitizeEmojisInFormBody($body);
    }

    $form = Form::createOrUpdate([
      'id' => $formId,
      'name' => $name,
      'body' => $body,
      'settings' => $settings,
      'styles' => $styles,
      'status' => $status,
    ]);

    $errors = $form->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    }
    if (isset($data['editor_version']) && $data['editor_version'] === "2") {
      $this->userFlags->set('display_new_form_editor_nps_survey', true);
    }

    $form = Form::findOne($form->id);
    if(!$form instanceof Form) return $this->errorResponse();
    return $this->successResponse(
      $form->asArray(),
      ['is_widget' => $isWidget]
    );
  }

  public function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form->restore();
      $form = Form::findOne($form->id);
      if(!$form instanceof Form) return $this->errorResponse();
      return $this->successResponse(
        $form->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form->trash();
      $form = Form::findOne($form->id);
      if(!$form instanceof Form) return $this->errorResponse();
      return $this->successResponse(
        $form->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form->delete();

      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function duplicate($data = []) {
    $form = $this->getForm($data);

    if ($form instanceof FormEntity) {
      try {
        $duplicate = $this->formSaveController->duplicate($form);
      } catch (Exception $e) {
        return $this->errorResponse([
          APIError::UNKNOWN => __('Duplicating form failed.', 'mailpoet'),
        ], [], Response::STATUS_UNKNOWN);
      }
      return $this->successResponse(
        $this->formsResponseBuilder->build($duplicate),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function bulkAction($data = []) {
    try {
      $meta = $this->bulkAction->apply('\MailPoet\Models\Form', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  private function getForm(array $data): ?FormEntity {
    return isset($data['id'])
      ? $this->formsRepository->findOneById((int)$data['id'])
      : null;
  }
}
