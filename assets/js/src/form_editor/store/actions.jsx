
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
