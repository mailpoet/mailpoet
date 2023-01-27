import { MailPoet } from 'mailpoet';
import { createCustomFieldDone } from './reducers/create_custom_field_done.jsx';
import { createCustomFieldFailed } from './reducers/create_custom_field_failed.jsx';
import { customFieldEdited } from './reducers/custom_field_edited.jsx';
import { createCustomFieldStartedFactory } from './reducers/create_custom_field_started.ts';
import { changeFormName } from './reducers/change_form_name.jsx';
import { changeFormSettings } from './reducers/change_form_settings.jsx';
import { changeFormStyles } from './reducers/change_form_styles.jsx';
import { removeNotice } from './reducers/remove_notice.jsx';
import { tutorialDismiss } from './reducers/tutorial_dismiss';
import {
  changePreviewSettings,
  hidePreview,
  previewDataNotSaved,
  previewDataSaved,
  showPreview,
} from './reducers/preview.jsx';
import { saveFormDone } from './reducers/save_form_done.jsx';
import { saveFormFailed } from './reducers/save_form_failed.jsx';
import { saveFormStartedFactory } from './reducers/save_form_started';
import { switchDefaultSidebarTab } from './reducers/switch_sidebar_tab.jsx';
import {
  toggleInserterSidebar,
  toggleSidebar,
} from './reducers/toggle_sidebar.ts';
import { toggleSidebarPanel } from './reducers/toggle_sidebar_panel.jsx';
import { changeFormBlocks } from './reducers/change_form_blocks.jsx';
import { saveCustomFieldDone } from './reducers/save_custom_field_done.jsx';
import { saveCustomFieldFailed } from './reducers/save_custom_field_failed.jsx';
import { saveCustomFieldStarted } from './reducers/save_custom_field_started.jsx';
import {
  customFieldDeleteDone,
  customFieldDeleteFailed,
  customFieldDeleteStart,
} from './reducers/custom_field_delete.jsx';
import { changeActiveSidebar } from './reducers/change_active_sidebar';
import { disableForm, enableForm } from './reducers/toggle_form';
import { toggleFullscreen } from './reducers/toggle_fullscreen';
import {
  createHistoryRecord,
  historyRedo,
  historyUndo,
} from './reducers/history_record';

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
