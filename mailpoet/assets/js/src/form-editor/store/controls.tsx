import { select, dispatch } from '@wordpress/data';
import { MailPoet } from 'mailpoet';
import { merge } from 'lodash';
import Cookies from 'js-cookie';
import {
  BlockInstance,
  createBlock,
  unregisterBlockType,
  getBlockType,
} from '@wordpress/blocks';
import { callApi as CALL_API } from 'common/controls/call-api';
import {
  SETTINGS_DEFAULTS,
  store as blockEditorStore,
} from '@wordpress/block-editor';

import { blocksToFormBodyFactory } from './blocks-to-form-body';
import { registerCustomFieldBlock } from '../blocks/blocks.jsx';
import { mapFormDataBeforeSaving } from './map-form-data-before-saving.jsx';
import { findBlock } from './find-block';
import { formatCustomFieldBlockName } from '../blocks/format-custom-field-block-name';
import { getCustomFieldBlockSettings } from '../blocks/custom-fields-blocks';
import { FONT_SIZES, storeName } from './constants';

const formatApiErrorMessage = (response) => {
  let errorMessage = null;
  if (Array.isArray(response.errors) && response.errors.length > 0) {
    errorMessage = response.errors.map((error) => error.message);
    errorMessage = errorMessage.join(', ');
  }
  return errorMessage;
};

// Recursively apply callback on every block in blocks tree
const mapBlocks = (
  blocks: Array<BlockInstance>,
  callback: (block: BlockInstance) => BlockInstance,
): BlockInstance[] =>
  blocks.map((block) => {
    const result = callback(block);
    if (block.innerBlocks) {
      return {
        ...result,
        innerBlocks: mapBlocks(block.innerBlocks, callback),
      };
    }
    return result;
  });

export const controls = {
  async SAVE_FORM() {
    if (select(storeName).getIsFormSaving()) {
      return;
    }
    void dispatch(storeName).saveFormStarted();
    const formErrors = select(storeName).getFormErrors();
    if (formErrors.length) {
      return;
    }
    const formData = select(storeName).getFormData();
    // Use blocks from block editor store to ensure we have the latest state
    const formBlocks = select(blockEditorStore).getBlocks();
    const customFields = select(storeName).getAllAvailableCustomFields();
    const blocksToFormBody = blocksToFormBodyFactory(
      FONT_SIZES,
      SETTINGS_DEFAULTS.colors,
      SETTINGS_DEFAULTS.gradients,
      customFields,
    );
    const requestData = {
      ...mapFormDataBeforeSaving(formData),
      body: blocksToFormBody(formBlocks),
      editor_version: 2,
    };
    await MailPoet.Ajax.post<{ data: { id: string } }>({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'saveEditor',
      data: requestData,
    })
      .done((result) => {
        void dispatch(storeName).saveFormDone(result.data.id);
        Cookies.remove(`popup_form_dismissed_${result.data.id}`, { path: '/' });
      })
      .fail((response) => {
        void dispatch(storeName).saveFormFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  async SAVE_CUSTOM_FIELD(actionData) {
    void dispatch(storeName).saveCustomFieldStarted();
    const customFields = select(storeName).getAllAvailableCustomFields();
    const customField = customFields.find(
      (cf) => cf.id === actionData.customFieldId,
    );
    const requestData = {};
    merge(requestData, customField, actionData.data);
    await MailPoet.Ajax.post<{ data: unknown }>({
      api_version: window.mailpoet_api_version,
      endpoint: 'customFields',
      action: 'save',
      data: requestData,
    })
      .then((response) => {
        void dispatch(storeName).saveCustomFieldDone(
          customField.id,
          response.data,
        );
        if (typeof actionData.onFinish === 'function') actionData.onFinish();
      })
      .then(() => void dispatch(storeName).saveForm())
      .fail((response) => {
        void dispatch(storeName).saveCustomFieldFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  async CREATE_CUSTOM_FIELD(action) {
    const {
      clientId,
      data,
    }: { clientId: string; data: Record<string, unknown> } = action;
    if (select(storeName).getIsCustomFieldCreating()) {
      return;
    }
    void dispatch(storeName).createCustomFieldStarted(action.data);
    // Check if it really started. Could been blocked by an error.
    if (!select(storeName).getIsCustomFieldCreating()) {
      return;
    }
    await MailPoet.Ajax.post<{ data: { type: string } }>({
      api_version: window.mailpoet_api_version,
      endpoint: 'customFields',
      action: 'save',
      data,
    })
      .then((response) => {
        const customField = response.data;
        MailPoet.trackEvent('Forms > Add new custom field', {
          'Field type': customField.type,
        });
        const blockName = registerCustomFieldBlock(customField);
        const customFieldBlock = createBlock(blockName);
        void dispatch(blockEditorStore).replaceBlock(
          clientId,
          customFieldBlock,
        );
        void dispatch(storeName).createCustomFieldDone(response.data);
      })
      .fail((response) => {
        void dispatch(storeName).createCustomFieldFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  async DELETE_CUSTOM_FIELD(actionData) {
    const {
      customFieldId,
      clientId,
    }: { customFieldId: number; clientId: string } = actionData;
    void dispatch(storeName).deleteCustomFieldStarted();
    const customFields = select(storeName).getAllAvailableCustomFields();
    const customField = customFields.find((cf) => cf.id === customFieldId);
    const namesMap = getCustomFieldBlockSettings(customField);
    await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'customFields',
      action: 'delete',
      data: {
        id: customFieldId,
      },
    })
      .then(() => {
        MailPoet.trackEvent('Forms > Delete custom field', {
          'Field type': customField.type,
        });
        void dispatch(storeName).deleteCustomFieldDone(customFieldId, clientId);
        const customFieldBlockName = formatCustomFieldBlockName(
          namesMap[customField.type].name,
          customField,
        );
        const customFieldBlock = getBlockType(customFieldBlockName);
        if (customFieldBlock) {
          unregisterBlockType(customFieldBlockName);
        }
        void dispatch(blockEditorStore).removeBlock(clientId);
      })
      .fail((response) => {
        void dispatch(storeName).deleteCustomFieldFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  APPLY_STYLES_TO_ALL_TEXT_INPUTS(actionData) {
    const currentBlocks = select(storeName).getFormBlocks();
    const updatedBlocks = mapBlocks(currentBlocks, (block) => {
      const updatedBlock = { ...block };
      if (
        [
          'mailpoet-form/last-name-input',
          'mailpoet-form/first-name-input',
          'mailpoet-form/email-input',
        ].includes(block.name) ||
        block.name.startsWith('mailpoet-form/custom-text')
      ) {
        return {
          ...updatedBlock,
          attributes: {
            ...updatedBlock.attributes,
            styles: actionData.styles,
          },
        };
      }
      return updatedBlock;
    });
    void dispatch(blockEditorStore).resetBlocks(updatedBlocks);
  },

  async TUTORIAL_DISMISS() {
    await MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'user_flags',
      action: 'set',
      data: { form_editor_tutorial_seen: 1 },
    });
  },

  /**
   * We want to ensure that email input and submit are always present.
   * @param actionData {{type: string, blocks: BlockInstance[]}} blocks property contains editor blocks
   */
  BLOCKS_CHANGED_IN_BLOCK_EDITOR(actionData) {
    const newBlocks = actionData.blocks as Array<BlockInstance>;
    // Check if both required inputs are present
    const emailInput = findBlock(newBlocks, 'mailpoet-form/email-input');
    const submitInput = findBlock(newBlocks, 'mailpoet-form/submit-button');
    if (emailInput && submitInput) {
      void dispatch(storeName).changeFormBlocks(newBlocks);
      return;
    }

    // In case that some of them is missing we restore it from previous state or insert new one
    const currentBlocks = select(storeName).getFormBlocks();
    const fixedBlocks = [...newBlocks];
    if (!emailInput) {
      let currentEmailInput = findBlock(
        currentBlocks,
        'mailpoet-form/email-input',
      );
      if (!currentEmailInput) {
        currentEmailInput = createBlock('mailpoet-form/email-input');
      }
      fixedBlocks.unshift(currentEmailInput);
    }
    if (!submitInput) {
      let currentSubmit = findBlock(
        currentBlocks,
        'mailpoet-form/submit-button',
      );
      if (!currentSubmit) {
        currentSubmit = createBlock('mailpoet-form/submit-button');
      }
      fixedBlocks.push(currentSubmit);
    }
    void dispatch(blockEditorStore).resetBlocks(fixedBlocks);
  },

  STORE_LOCALLY(actionData) {
    const { key, value } = actionData as Record<string, string>;
    window.localStorage.setItem(key, JSON.stringify(value));
  },

  CALL_API,

  ENSURE_BROWSER_URL(actionData) {
    const { formId } = actionData as Record<string, string>;
    let url = select(storeName).getFormEditorUrl();
    url = `${url}${formId}`;
    if (window.location.href !== url) {
      window.history.replaceState(null, '', url);
    }
  },
};
