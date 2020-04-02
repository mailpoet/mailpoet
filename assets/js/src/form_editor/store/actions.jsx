
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

export function setPlaceFormBellowAllPages(place) {
  return {
    type: 'PLACE_FORM_BELLOW_ALL_PAGES',
    place,
  };
}

export function setPlaceFormBellowAllPosts(place) {
  return {
    type: 'PLACE_FORM_BELLOW_ALL_POSTS',
    place,
  };
}

export function setPlacePopupFormOnAllPages(place) {
  return {
    type: 'PLACE_POPUP_FORM_ON_ALL_PAGES',
    place,
  };
}

export function setPlacePopupFormOnAllPosts(place) {
  return {
    type: 'PLACE_POPUP_FORM_ON_ALL_POSTS',
    place,
  };
}

export function setPopupFormDelay(delay) {
  return {
    type: 'SET_POPUP_FORM_DELAY',
    delay,
  };
}

export function setPlaceFixedBarFormOnAllPages(place) {
  return {
    type: 'PLACE_FIXED_BAR_FORM_ON_ALL_PAGES',
    place,
  };
}

export function setPlaceFixedBarFormOnAllPosts(place) {
  return {
    type: 'PLACE_FIXED_BAR_FORM_ON_ALL_POSTS',
    place,
  };
}

export function setFixedBarFormDelay(delay) {
  return {
    type: 'SET_FIXED_BAR_FORM_DELAY',
    delay,
  };
}

export function setFixedBarFormPosition(position) {
  return {
    type: 'SET_FIXED_BAR_FORM_POSITION',
    position,
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

export function showPreview() {
  return {
    type: 'SHOW_PREVIEW',
  };
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

export function switchSidebarTab(id) {
  return {
    type: 'SWITCH_SIDEBAR_TAB',
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
