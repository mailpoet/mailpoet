import { select } from '@wordpress/data';
import blocksToFormBodyFactory from './blocks_to_form_body';
import mapFormDataBeforeSaving from './map_form_data_before_saving';

export function toggleSidebar(toggleTo) {
  return {
    type: 'TOGGLE_SIDEBAR',
    toggleTo,
  };
}

export function changeFormBlocks(blocks) {
  return {
    type: 'CHANGE_FORM_BLOCKS',
    blocks,
  };
}

export function changeFormName(name) {
  return {
    type: 'CHANGE_FORM_NAME',
    name,
  };
}

export function deleteCustomFieldStarted() {
  return {
    type: 'DELETE_CUSTOM_FIELD_STARTED',
  };
}

export function deleteCustomFieldDone(customFieldId, clientId) {
  return {
    type: 'DELETE_CUSTOM_FIELD_DONE',
    customFieldId,
    clientId,
  };
}

export function deleteCustomFieldFailed(message = undefined) {
  return {
    type: 'DELETE_CUSTOM_FIELD_FAILED',
    message,
  };
}
export function changeFormStyles(styles) {
  return {
    type: 'CHANGE_FORM_STYLES',
    styles,
  };
}

export function customFieldEdited() {
  return {
    type: 'CUSTOM_FIELD_EDITED',
  };
}

export function saveCustomFieldDone(customFieldId, response) {
  return {
    type: 'SAVE_CUSTOM_FIELD_DONE',
    customFieldId,
    response,
  };
}

export function saveCustomFieldStarted() {
  return {
    type: 'SAVE_CUSTOM_FIELD_STARTED',
  };
}

export function saveCustomFieldFailed(message = undefined) {
  return {
    type: 'SAVE_CUSTOM_FIELD_FAILED',
    message,
  };
}

export function createCustomFieldDone(response) {
  return {
    type: 'CREATE_CUSTOM_FIELD_DONE',
    response,
  };
}

export function createCustomFieldStarted(customField) {
  return {
    type: 'CREATE_CUSTOM_FIELD_STARTED',
    customField,
  };
}

export function createCustomFieldFailed(message = undefined) {
  return {
    type: 'CREATE_CUSTOM_FIELD_FAILED',
    message,
  };
}

export function changeFormSettings(settings) {
  return {
    type: 'CHANGE_FORM_SETTINGS',
    settings,
  };
}

export function saveFormDone(result) {
  return {
    type: 'SAVE_FORM_DONE',
    result,
  };
}

export function saveFormStarted() {
  return {
    type: 'SAVE_FORM_STARTED',
  };
}

export function saveFormFailed(message = undefined) {
  return {
    type: 'SAVE_FORM_FAILED',
    message,
  };
}

export function* changePreviewSettings(settings) {
  yield {
    type: 'STORE_LOCALLY',
    key: 'mailpoet_form_preview_settings',
    value: settings,
  };
  yield {
    type: 'CHANGE_PREVIEW_SETTINGS',
    settings,
  };
}

export function* showPreview(formType = null) {
  if (formType !== null && typeof formType === 'string') {
    const previewSettings = select('mailpoet-form-editor').getPreviewSettings();
    const updatedPreviewSettings = {
      ...previewSettings,
      formType,
    };
    yield* changePreviewSettings(updatedPreviewSettings);
  }
  yield {
    type: 'SHOW_PREVIEW',
  };
  const editorSettings = select('core/block-editor').getSettings();
  const customFields = select('mailpoet-form-editor').getAllAvailableCustomFields();
  const formData = select('mailpoet-form-editor').getFormData();
  const formBlocks = select('mailpoet-form-editor').getFormBlocks();
  const blocksToFormBody = blocksToFormBodyFactory(
    editorSettings.fontSizes,
    editorSettings.colors,
    editorSettings.gradients,
    customFields
  );
  const { success, error } = yield {
    type: 'CALL_API',
    endpoint: 'forms',
    action: 'previewEditor',
    data: {
      ...mapFormDataBeforeSaving(formData),
      body: blocksToFormBody(formBlocks),
    },
  };
  if (!success) {
    return { type: 'PREVIEW_DATA_NOT_SAVED', error };
  }
  return { type: 'PREVIEW_DATA_SAVED' };
}

export function hidePreview() {
  return {
    type: 'HIDE_PREVIEW',
  };
}

export function removeNotice(id) {
  return {
    type: 'REMOVE_NOTICE',
    id,
  };
}

export function switchDefaultSidebarTab(id) {
  return {
    type: 'SWITCH_DEFAULT_SIDEBAR_TAB',
    id,
  };
}

/**
 * Toggle a panel within the sidebar. Use toggleTo to enforce certain state
 * @param {string} id
 * @param {string|undefined} toggleTo - possible values 'opened', 'closed'
 * @return {{toggleTo: string|undefined, id: string, type: string}}
 */
export function toggleSidebarPanel(id, toggleTo = undefined) {
  return {
    type: 'TOGGLE_SIDEBAR_PANEL',
    id,
    toggleTo,
  };
}

export function* saveForm() {
  yield {
    type: 'SAVE_FORM',
  };
}

export function* saveCustomField(data) {
  yield {
    type: 'SAVE_CUSTOM_FIELD',
    ...data,
  };
}

export function* createCustomField(data, clientId) {
  yield {
    type: 'CREATE_CUSTOM_FIELD',
    clientId,
    data,
  };
}

export function* deleteCustomField(customFieldId, clientId) {
  yield {
    type: 'DELETE_CUSTOM_FIELD',
    customFieldId,
    clientId,
  };
}

export function* blocksChangedInBlockEditor(blocks) {
  yield {
    type: 'BLOCKS_CHANGED_IN_BLOCK_EDITOR',
    blocks,
  };
}

export function* applyStylesToAllTextInputs(styles) {
  yield {
    type: 'APPLY_STYLES_TO_ALL_TEXT_INPUTS',
    styles,
  };
}
