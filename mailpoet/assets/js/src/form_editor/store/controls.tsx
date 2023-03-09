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
import { callApi as CALL_API } from 'common/controls/call_api';
import {
  SETTINGS_DEFAULTS,
  store as blockEditorStore,
} from '@wordpress/block-editor';
import {
  ReduxStoreConfig,
  StoreDescriptor,
} from '@wordpress/data/build-types/types';

import { blocksToFormBodyFactory } from './blocks_to_form_body';
import { registerCustomFieldBlock } from '../blocks/blocks.jsx';
import { mapFormDataBeforeSaving } from './map_form_data_before_saving.jsx';
import { findBlock } from './find_block';
import { formatCustomFieldBlockName } from '../blocks/format_custom_field_block_name';
import { getCustomFieldBlockSettings } from '../blocks/custom_fields_blocks';
import { State } from './state_types';
import * as actions from './actions';
import { selectors } from './selectors';

// workaround to avoid import cycles
const store = { name: 'mailpoet-form-editor' } as StoreDescriptor<
  ReduxStoreConfig<State, typeof actions, typeof selectors>
>;

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
    if (select(store).getIsFormSaving()) {
      return;
    }
    void dispatch(store).saveFormStarted();
    const formErrors = select(store).getFormErrors();
    if (formErrors.length) {
      return;
    }
    const formData = select(store).getFormData();
    const formBlocks = select(store).getFormBlocks();
    const customFields = select(store).getAllAvailableCustomFields();
    const blocksToFormBody = blocksToFormBodyFactory(
      SETTINGS_DEFAULTS.fontSizes,
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
        void dispatch(store).saveFormDone(result.data.id);
        Cookies.remove(`popup_form_dismissed_${result.data.id}`, { path: '/' });
      })
      .fail((response) => {
        void dispatch(store).saveFormFailed(formatApiErrorMessage(response));
      });
  },

  async SAVE_CUSTOM_FIELD(actionData) {
    void dispatch(store).saveCustomFieldStarted();
    const customFields = select(store).getAllAvailableCustomFields();
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
        void dispatch(store).saveCustomFieldDone(customField.id, response.data);
        if (typeof actionData.onFinish === 'function') actionData.onFinish();
      })
      .then(() => void dispatch(store).saveForm())
      .fail((response) => {
        void dispatch(store).saveCustomFieldFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  async CREATE_CUSTOM_FIELD(action) {
    const {
      clientId,
      data,
    }: { clientId: string; data: Record<string, unknown> } = action;
    if (select(store).getIsCustomFieldCreating()) {
      return;
    }
    void dispatch(store).createCustomFieldStarted(action.data);
    // Check if it really started. Could been blocked by an error.
    if (!select(store).getIsCustomFieldCreating()) {
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
        dispatch(blockEditorStore).replaceBlock(clientId, customFieldBlock);
        void dispatch(store).createCustomFieldDone(response.data);
      })
      .fail((response) => {
        void dispatch(store).createCustomFieldFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  async DELETE_CUSTOM_FIELD(actionData) {
    const {
      customFieldId,
      clientId,
    }: { customFieldId: number; clientId: string } = actionData;
    void dispatch(store).deleteCustomFieldStarted();
    const customFields = select(store).getAllAvailableCustomFields();
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
        void dispatch(store).deleteCustomFieldDone(customFieldId, clientId);
        const customFieldBlockName = formatCustomFieldBlockName(
          namesMap[customField.type].name,
          customField,
        );
        const customFieldBlock = getBlockType(customFieldBlockName);
        if (customFieldBlock) {
          unregisterBlockType(customFieldBlockName);
        }
        dispatch(blockEditorStore).removeBlock(clientId);
      })
      .fail((response) => {
        void dispatch(store).deleteCustomFieldFailed(
          formatApiErrorMessage(response),
        );
      });
  },

  APPLY_STYLES_TO_ALL_TEXT_INPUTS(actionData) {
    const currentBlocks = select(store).getFormBlocks();
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
    dispatch(blockEditorStore).resetBlocks(updatedBlocks);
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
      void dispatch(store).changeFormBlocks(newBlocks);
      return;
    }

    // In case that some of them is missing we restore it from previous state or insert new one
    const currentBlocks = select(store).getFormBlocks();
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
    dispatch(blockEditorStore).resetBlocks(fixedBlocks);
  },

  STORE_LOCALLY(actionData) {
    const { key, value } = actionData as Record<string, string>;
    window.localStorage.setItem(key, JSON.stringify(value));
  },

  CALL_API,

  ENSURE_BROWSER_URL(actionData) {
    const { formId } = actionData as Record<string, string>;
    let url = select(store).getFormEditorUrl();
    url = `${url}${formId}`;
    if (window.location.href !== url) {
      window.history.replaceState(null, '', url);
    }
  },
};
