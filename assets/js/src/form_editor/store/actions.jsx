
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

export function saveFormDone(result) {
  return {
    type: 'SAVE_FORM_DONE',
    result,
  };
}

const createAddNoticeAction = (content, status, isDismissible, id) => ({
  type: 'ADD_NOTICE',
  content,
  status,
  isDismissible,
  id,
});

export function addNotice(content, status, id) {
  return createAddNoticeAction(content, status, false, id);
}

export function addDismissibleNotice(content, status, id) {
  return createAddNoticeAction(content, status, true, id);
}

export function removeNotice(id) {
  return {
    type: 'REMOVE_NOTICE',
    id,
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
