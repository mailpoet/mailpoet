import { MailPoet } from 'mailpoet';
import { createCustomFieldDone } from './reducers/create-custom-field-done.jsx';
import { createCustomFieldFailed } from './reducers/create-custom-field-failed.jsx';
import { customFieldEdited } from './reducers/custom-field-edited.jsx';
import { createCustomFieldStartedFactory } from './reducers/create-custom-field-started.ts';
import { changeFormName } from './reducers/change-form-name.jsx';
import { changeFormSettings } from './reducers/change-form-settings.jsx';
import { changeFormStyles } from './reducers/change-form-styles.jsx';
import { removeNotice } from './reducers/remove-notice.jsx';
import { tutorialDismiss } from './reducers/tutorial-dismiss';
import {
  changePreviewSettings,
  hidePreview,
  previewDataNotSaved,
  previewDataSaved,
  showPreview,
} from './reducers/preview.jsx';
import { saveFormDone } from './reducers/save-form-done.jsx';
import { saveFormFailed } from './reducers/save-form-failed.jsx';
import { saveFormStartedFactory } from './reducers/save-form-started';
import { switchDefaultSidebarTab } from './reducers/switch-sidebar-tab.jsx';
import {
  toggleInserterSidebar,
  toggleSidebar,
  toggleListView,
} from './reducers/toggle-sidebar.ts';
import { toggleSidebarPanel } from './reducers/toggle-sidebar-panel.ts';
import { changeFormBlocks } from './reducers/change-form-blocks.jsx';
import { saveCustomFieldDone } from './reducers/save-custom-field-done.jsx';
import { saveCustomFieldFailed } from './reducers/save-custom-field-failed.jsx';
import { saveCustomFieldStarted } from './reducers/save-custom-field-started.jsx';
import {
  customFieldDeleteDone,
  customFieldDeleteFailed,
  customFieldDeleteStart,
} from './reducers/custom-field-delete.jsx';
import { changeActiveSidebar } from './reducers/change-active-sidebar';
import { disableForm, enableForm } from './reducers/toggle-form';
import { toggleFullscreen } from './reducers/toggle-fullscreen';
import {
  createHistoryRecord,
  historyRedo,
  historyUndo,
} from './reducers/history-record';

const createCustomFieldStarted = createCustomFieldStartedFactory(MailPoet);
const saveFormStarted = saveFormStartedFactory(MailPoet);

const mainReducer = (state, action) => {
  switch (action.type) {
    case 'ENABLE_FORM':
      return enableForm(state);
    case 'DISABLE_FORM':
      return disableForm(state);
    case 'TOGGLE_FULLSCREEN':
      return toggleFullscreen(state, action);
    case 'CREATE_CUSTOM_FIELD_DONE':
      return createCustomFieldDone(state, action);
    case 'CREATE_CUSTOM_FIELD_FAILED':
      return createCustomFieldFailed(state, action);
    case 'CREATE_CUSTOM_FIELD_STARTED':
      return createCustomFieldStarted(state, action);
    case 'CHANGE_FORM_BLOCKS':
      return changeFormBlocks(state, action);
    case 'CHANGE_FORM_NAME':
      return changeFormName(state, action);
    case 'CHANGE_FORM_SETTINGS':
      return changeFormSettings(state, action);
    case 'CHANGE_FORM_STYLES':
      return changeFormStyles(state, action);
    case 'CHANGE_PREVIEW_SETTINGS':
      return changePreviewSettings(state, action);
    case 'CUSTOM_FIELD_EDITED':
      return customFieldEdited(state);
    case 'REMOVE_NOTICE':
      return removeNotice(state, action);
    case 'SHOW_PREVIEW':
      return showPreview(state, action);
    case 'HIDE_PREVIEW':
      return hidePreview(state, action);
    case 'PREVIEW_DATA_NOT_SAVED':
      return previewDataNotSaved(state, action);
    case 'PREVIEW_DATA_SAVED':
      return previewDataSaved(state, action);
    case 'SAVE_FORM_DONE':
      return saveFormDone(state, action);
    case 'SAVE_FORM_FAILED':
      return saveFormFailed(state, action);
    case 'SAVE_FORM_STARTED':
      return saveFormStarted(state);
    case 'SAVE_CUSTOM_FIELD_DONE':
      return saveCustomFieldDone(state, action);
    case 'SAVE_CUSTOM_FIELD_FAILED':
      return saveCustomFieldFailed(state, action);
    case 'SAVE_CUSTOM_FIELD_STARTED':
      return saveCustomFieldStarted(state);
    case 'SWITCH_DEFAULT_SIDEBAR_TAB':
      return switchDefaultSidebarTab(state, action);
    case 'TOGGLE_SIDEBAR':
      return toggleSidebar(state, action);
    case 'TOGGLE_INSERTER_SIDEBAR':
      return toggleInserterSidebar(state, action);
    case 'TOGGLE_LIST_VIEW':
      return toggleListView(state);
    case 'TOGGLE_SIDEBAR_PANEL':
      return toggleSidebarPanel(state, action);
    case 'DELETE_CUSTOM_FIELD_STARTED':
      return customFieldDeleteStart(state, action);
    case 'DELETE_CUSTOM_FIELD_DONE':
      return customFieldDeleteDone(state, action);
    case 'DELETE_CUSTOM_FIELD_FAILED':
      return customFieldDeleteFailed(state, action);
    case 'CHANGE_ACTIVE_SIDEBAR':
      return changeActiveSidebar(state, action);
    case 'HISTORY_UNDO':
      return historyUndo(state);
    case 'HISTORY_REDO':
      return historyRedo(state);
    case 'TUTORIAL_DISMISSED':
      return tutorialDismiss(state);
    default:
      return state;
  }
};

const undoRedoReducer = (state, action) => {
  if (
    action.type === 'CHANGE_FORM_BLOCKS' ||
    action.type === 'CHANGE_FORM_NAME' ||
    action.type === 'CHANGE_FORM_SETTINGS' ||
    action.type === 'CHANGE_FORM_STYLES'
  ) {
    return createHistoryRecord(state);
  }

  return state;
};

export const createReducer =
  (defaultState) =>
  (state = defaultState, action = {}) => {
    const stateAfterUndo = undoRedoReducer(state, action);
    return mainReducer(stateAfterUndo, action);
  };
