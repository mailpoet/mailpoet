
export function toggleSidebar(toggleTo) {
  return {
    type: 'TOGGLE_SIDEBAR',
    toggleTo,
  };
}

export function changeFormName(name) {
  return {
    type: 'CHANGE_FORM_NAME',
    name,
  };
}

export function* saveForm() {
  yield {
    type: 'SAVE_FORM_STARTED',
  };
  yield {
    type: 'SAVE_FORM',
  };
}

export function saveFormDone(result) {
  return {
    type: 'SAVE_FORM_DONE',
    result,
  };
}

export function addNotice(content, status, isDismissible = false, id = null) {
  return {
    type: 'ADD_NOTICE',
    content,
    status,
    isDismissible,
    id,
  };
}

export function removeNotice(id) {
  return {
    type: 'REMOVE_NOTICE',
    id,
  };
}
