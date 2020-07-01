import { select, dispatch } from '@wordpress/data';
import MailPoet from 'mailpoet';
import { merge } from 'lodash';
import Cookies from 'js-cookie';
import { createBlock, unregisterBlockType } from '@wordpress/blocks';
import CALL_API from 'common/controls/call_api';
import blocksToFormBodyFactory from './blocks_to_form_body.jsx';
import formatCustomFieldBlockName from '../blocks/format_custom_field_block_name.jsx';
import getCustomFieldBlockSettings from '../blocks/custom_fields_blocks.jsx';
import { registerCustomFieldBlock } from '../blocks/blocks.jsx';
import mapFormDataBeforeSaving from './map_form_data_before_saving.jsx';
import findBlock from './find_block.jsx';

const formatApiErrorMessage = (response) => {
  let errorMessage = null;
  if (response.errors.length > 0) {
    errorMessage = response.errors.map((error) => (error.message));
    errorMessage = errorMessage.join(', ');
  }
  return errorMessage;
};

// Recursively apply callback on every block in blocks tree
const mapBlocks = (blocks, callback) => blocks.map((block) => {
  const result = callback(block);
  if (block.innerBlocks) {
    result.innerBlocks = mapBlocks(block.innerBlocks, callback);
  }
  return result;
});

export default {
  SAVE_FORM() {
    if (select('mailpoet-form-editor').getIsFormSaving()) {
      return;
    }
    dispatch('mailpoet-form-editor').saveFormStarted();
    const formErrors = select('mailpoet-form-editor').getFormErrors();
    if (formErrors.length) {
      return;
    }
    const formData = select('mailpoet-form-editor').getFormData();
    const formBlocks = select('mailpoet-form-editor').getFormBlocks();
    const customFields = select('mailpoet-form-editor').getAllAvailableCustomFields();
    const { getSettings } = select('core/block-editor');
    const settings = getSettings();
    const blocksToFormBody = blocksToFormBodyFactory(
      settings.colors,
      settings.fontSizes,
      customFields
    );
    const requestData = {
      ...mapFormDataBeforeSaving(formData),
      body: blocksToFormBody(formBlocks),
      editor_version: 2,
    };
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'saveEditor',
      data: requestData,
    }).done(() => {
      dispatch('mailpoet-form-editor').saveFormDone();
      Cookies.remove('popup_form_dismissed', { path: '/' });
    }).fail((response) => {
      dispatch('mailpoet-form-editor').saveFormFailed(formatApiErrorMessage(response));
    });
  },

  SAVE_CUSTOM_FIELD(actionData) {
    dispatch('mailpoet-form-editor').saveCustomFieldStarted();
    const customFields = select('mailpoet-form-editor').getAllAvailableCustomFields();
    const customField = customFields.find((cf) => cf.id === actionData.customFieldId);
    const requestData = {};
    merge(requestData, customField, actionData.data);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'customFields',
      action: 'save',
      data: requestData,
    })
      .then((response) => {
        dispatch('mailpoet-form-editor').saveCustomFieldDone(customField.id, response.data);
        if (typeof actionData.onFinish === 'function') actionData.onFinish();
      })
      .then(dispatch('mailpoet-form-editor').saveForm)
      .fail((response) => {
        dispatch('mailpoet-form-editor').saveCustomFieldFailed(formatApiErrorMessage(response));
      });
  },

  CREATE_CUSTOM_FIELD(action) {
    if (select('mailpoet-form-editor').getIsCustomFieldCreating()) {
      return;
    }
    dispatch('mailpoet-form-editor').createCustomFieldStarted(action.data);
    // Check if it really started. Could been blocked by an error.
    if (!select('mailpoet-form-editor').getIsCustomFieldCreating()) {
      return;
    }
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'customFields',
      action: 'save',
      data: action.data,
    })
      .then((response) => {
        const customField = response.data;
        MailPoet.trackEvent('Forms > Add new custom field', {
          'Field type': customField.type,
          'MailPoet Free version': window.mailpoet_version,
        });
        const blockName = registerCustomFieldBlock(customField);
        const customFieldBlock = createBlock(blockName);
        dispatch('core/block-editor').replaceBlock(action.clientId, customFieldBlock);
        dispatch('mailpoet-form-editor').createCustomFieldDone(response.data);
      })
      .fail((response) => {
        dispatch('mailpoet-form-editor').createCustomFieldFailed(formatApiErrorMessage(response));
      });
  },

  DELETE_CUSTOM_FIELD(actionData) {
    dispatch('mailpoet-form-editor').deleteCustomFieldStarted();
    const customFields = select('mailpoet-form-editor').getAllAvailableCustomFields();
    const customField = customFields.find((cf) => cf.id === actionData.customFieldId);
    const namesMap = getCustomFieldBlockSettings(customField);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'customFields',
      action: 'delete',
      data: {
        id: actionData.customFieldId,
      },
    })
      .then(() => {
        MailPoet.trackEvent('Forms > Delete custom field', {
          'Field type': customField.type,
          'MailPoet Free version': window.mailpoet_version,
        });
        dispatch('mailpoet-form-editor').deleteCustomFieldDone(actionData.customFieldId, actionData.clientId);
        dispatch('core/block-editor').removeBlock(actionData.clientId);
        unregisterBlockType(
          formatCustomFieldBlockName(namesMap[customField.type].name, customField)
        );
      })
      .fail((response) => {
        dispatch('mailpoet-form-editor').deleteCustomFieldFailed(formatApiErrorMessage(response));
      });
  },

  APPLY_STYLES_TO_ALL_TEXT_INPUTS(actionData) {
    const currentBlocks = select('mailpoet-form-editor').getFormBlocks();
    const updatedBlocks = mapBlocks(currentBlocks, (block) => {
      const updatedBlock = { ...block };
      if (
        ['mailpoet-form/last-name-input', 'mailpoet-form/first-name-input', 'mailpoet-form/email-input'].includes(block.name)
        || block.name.startsWith('mailpoet-form/custom-text')
      ) {
        updatedBlock.attributes.styles = actionData.styles;
      }
      return updatedBlock;
    });
    dispatch('core/block-editor').resetBlocks(updatedBlocks);
  },

  /**
   * We want to ensure that email input and submit are always present.
   * @param actionData {{type: string, blocks: Object[]}} blocks property contains editor blocks
   */
  BLOCKS_CHANGED_IN_BLOCK_EDITOR(actionData) {
    const newBlocks = actionData.blocks;
    // Check if both required inputs are present
    const emailInput = findBlock(newBlocks, 'mailpoet-form/email-input');
    const submitInput = findBlock(newBlocks, 'mailpoet-form/submit-button');
    if (emailInput && submitInput) {
      dispatch('mailpoet-form-editor').changeFormBlocks(newBlocks);
      return;
    }

    // In case that some of them is missing we restore it from previous state or insert new one
    const currentBlocks = select('mailpoet-form-editor').getFormBlocks();
    const fixedBlocks = [...newBlocks];
    if (!emailInput) {
      let currentEmailInput = findBlock(currentBlocks, 'mailpoet-form/email-input');
      if (!currentEmailInput) {
        currentEmailInput = createBlock('mailpoet-form/email-input');
      }
      fixedBlocks.unshift(currentEmailInput);
    }
    if (!submitInput) {
      let currentSubmit = findBlock(currentBlocks, 'mailpoet-form/submit-button');
      if (!currentSubmit) {
        currentSubmit = createBlock('mailpoet-form/submit-button');
      }
      fixedBlocks.push(currentSubmit);
    }
    dispatch('core/block-editor').resetBlocks(fixedBlocks);
  },

  STORE_LOCALLY({ key, value }) {
    window.localStorage.setItem(key, JSON.stringify(value));
  },

  CALL_API,
};
