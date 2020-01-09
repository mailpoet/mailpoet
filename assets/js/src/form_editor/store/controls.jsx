import { select, dispatch } from '@wordpress/data';
import MailPoet from 'mailpoet';
import { merge } from 'lodash';
import { unregisterBlockType } from '@wordpress/blocks';
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
};
