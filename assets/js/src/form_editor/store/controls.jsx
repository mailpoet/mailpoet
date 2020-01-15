import { select, dispatch } from '@wordpress/data';
import MailPoet from 'mailpoet';
import { merge } from 'lodash';
import { unregisterBlockType, createBlock } from '@wordpress/blocks';
import blocksToFormBody from './blocks_to_form_body.jsx';
import formatCustomFieldBlockName from '../blocks/format_custom_field_block_name.jsx';
import getCustomFieldBlockSettings from '../blocks/custom_fields_blocks.jsx';

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
    const requestData = {
      ...formData,
      body: blocksToFormBody(formBlocks, customFields),
    };
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'saveEditor',
      data: requestData,
    }).done(() => {
      dispatch('mailpoet-form-editor').saveFormDone();
    }).fail((response) => {
      let errorMessage = null;
      if (response.errors.length > 0) {
        errorMessage = response.errors.map((error) => (error.message));
      }
      dispatch('mailpoet-form-editor').saveFormFailed(errorMessage);
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
        let errorMessage = null;
        if (response.errors.length > 0) {
          errorMessage = response.errors.map((error) => (error.message));
        }
        dispatch('mailpoet-form-editor').saveCustomFieldFailed(errorMessage);
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
        dispatch('mailpoet-form-editor').deleteCustomFieldDone(actionData.customFieldId, actionData.clientId);
        dispatch('core/block-editor').removeBlock(actionData.clientId);
        unregisterBlockType(
          formatCustomFieldBlockName(namesMap[customField.type].name, customField)
        );
      })
      .fail((response) => {
        let errorMessage = null;
        if (response.errors.length > 0) {
          errorMessage = response.errors.map((error) => (error.message));
        }
        dispatch('mailpoet-form-editor').deleteCustomFieldFailed(errorMessage);
      });
  },

  /**
   * We want to ensure that email input and submit are always present.
   * @param actionData {{type: string, blocks: Object[]}} blocks property contains editor blocks
   */
  BLOCKS_CHANGED_IN_BLOCK_EDITOR(actionData) {
    const newBlocks = actionData.blocks;
    // Check if both required inputs are present
    const emailInput = newBlocks.find((block) => block.name === 'mailpoet-form/email-input');
    const submitInput = newBlocks.find((block) => block.name === 'mailpoet-form/submit-button');
    if (emailInput && submitInput) {
      dispatch('mailpoet-form-editor').changeFormBlocks(newBlocks);
      return;
    }

    // In case that some of them is missing we restore it from previous state or insert new one
    const currentBlocks = select('mailpoet-form-editor').getFormBlocks();
    const fixedBlocks = [...newBlocks];
    if (!emailInput) {
      let currentEmailInput = currentBlocks.find((block) => block.name === 'mailpoet-form/email-input');
      if (!currentEmailInput) {
        currentEmailInput = createBlock('mailpoet-form/email-input');
      }
      fixedBlocks.unshift(currentEmailInput);
    }
    if (!submitInput) {
      let currentSubmit = currentBlocks.find((block) => block.name === 'mailpoet-form/submit-button');
      if (!currentSubmit) {
        currentSubmit = createBlock('mailpoet-form/submit-button');
      }
      fixedBlocks.push(currentSubmit);
    }
    dispatch('core/block-editor').resetBlocks(fixedBlocks);
  },
};
