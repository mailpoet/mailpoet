import MailPoet from 'mailpoet';
import createCustomFieldDone from './reducers/create_custom_field_done.jsx';
import createCustomFieldFailed from './reducers/create_custom_field_failed.jsx';
import customFieldEdited from './reducers/custom_field_edited.jsx';
import createCustomFieldStartedFactory from './reducers/create_custom_field_started.jsx';
import changeFormName from './reducers/change_form_name.jsx';
import {
  placeFormBellowAllPages,
  placeFormBellowAllPosts,
} from './reducers/form_placement.jsx';
import changeFormSettings from './reducers/change_form_settings.jsx';
import changeFormStyles from './reducers/change_form_styles.jsx';
import removeNotice from './reducers/remove_notice.jsx';
import { showPreview, hidePreview } from './reducers/preview.jsx';
import saveFormDone from './reducers/save_form_done.jsx';
import saveFormFailed from './reducers/save_form_failed.jsx';
import saveFormStartedFactory from './reducers/save_form_started.jsx';
import switchSidebarTab from './reducers/switch_sidebar_tab.jsx';
import toggleSidebar from './reducers/toggle_sidebar.jsx';
import toggleSidebarPanel from './reducers/toggle_sidebar_panel.jsx';
import changeFormBlocks from './reducers/change_form_blocks.jsx';
import saveCustomFieldDone from './reducers/save_custom_field_done.jsx';
import saveCustomFieldFailed from './reducers/save_custom_field_failed.jsx';
import saveCustomFieldStarted from './reducers/save_custom_field_started.jsx';
import {
  customFieldDeleteStart,
  customFieldDeleteDone,
  customFieldDeleteFailed,
} from './reducers/custom_field_delete.jsx';

const createCustomFieldStarted = createCustomFieldStartedFactory(MailPoet);
const saveFormStarted = saveFormStartedFactory(MailPoet);

export default (defaultState) => (state = defaultState, action) => {
  switch (action.type) {
    case 'CREATE_CUSTOM_FIELD_DONE': return createCustomFieldDone(state, action);
    case 'CREATE_CUSTOM_FIELD_FAILED': return createCustomFieldFailed(state, action);
    case 'CREATE_CUSTOM_FIELD_STARTED': return createCustomFieldStarted(state, action);
    case 'CHANGE_FORM_BLOCKS': return changeFormBlocks(state, action);
    case 'CHANGE_FORM_NAME': return changeFormName(state, action);
    case 'CHANGE_FORM_SETTINGS': return changeFormSettings(state, action);
    case 'CHANGE_FORM_STYLES': return changeFormStyles(state, action);
    case 'CUSTOM_FIELD_EDITED': return customFieldEdited(state);
    case 'REMOVE_NOTICE': return removeNotice(state, action);
    case 'SHOW_PREVIEW': return showPreview(state, action);
    case 'HIDE_PREVIEW': return hidePreview(state, action);
    case 'SAVE_FORM_DONE': return saveFormDone(state);
    case 'SAVE_FORM_FAILED': return saveFormFailed(state, action);
    case 'SAVE_FORM_STARTED': return saveFormStarted(state);
    case 'SAVE_CUSTOM_FIELD_DONE': return saveCustomFieldDone(state, action);
    case 'SAVE_CUSTOM_FIELD_FAILED': return saveCustomFieldFailed(state, action);
    case 'SAVE_CUSTOM_FIELD_STARTED': return saveCustomFieldStarted(state);
    case 'SWITCH_SIDEBAR_TAB': return switchSidebarTab(state, action);
    case 'TOGGLE_SIDEBAR': return toggleSidebar(state, action);
    case 'TOGGLE_SIDEBAR_PANEL': return toggleSidebarPanel(state, action);
    case 'DELETE_CUSTOM_FIELD_STARTED': return customFieldDeleteStart(state, action);
    case 'DELETE_CUSTOM_FIELD_DONE': return customFieldDeleteDone(state, action);
    case 'DELETE_CUSTOM_FIELD_FAILED': return customFieldDeleteFailed(state, action);
    case 'PLACE_FORM_BELLOW_ALL_PAGES': return placeFormBellowAllPages(state, action);
    case 'PLACE_FORM_BELLOW_ALL_POSTS': return placeFormBellowAllPosts(state, action);
    default:
      return state;
  }
};
