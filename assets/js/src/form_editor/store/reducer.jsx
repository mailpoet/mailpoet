import MailPoet from 'mailpoet';
import changeFormName from './reducers/change_form_name.jsx';
import changeFormSettings from './reducers/change_form_settings.jsx';
import changeFormStyles from './reducers/change_form_styles.jsx';
import removeNotice from './reducers/remove_notice.jsx';
import saveFormDone from './reducers/save_form_done.jsx';
import saveFormFailed from './reducers/save_form_failed.jsx';
import saveFormStartedFactory from './reducers/save_form_started.jsx';
import switchSidebarTab from './reducers/switch_sidebar_tab.jsx';
import toggleSidebar from './reducers/toggle_sidebar.jsx';
import toggleSidebarPanel from './reducers/toggle_sidebar_panel.jsx';
import changeFormBlocks from './reducers/change_form_blocks.jsx';

const saveFormStarted = saveFormStartedFactory(MailPoet);

export default (defaultState) => (state = defaultState, action) => {
  switch (action.type) {
    case 'CHANGE_FORM_BLOCKS': return changeFormBlocks(state, action);
    case 'CHANGE_FORM_NAME': return changeFormName(state, action);
    case 'CHANGE_FORM_SETTINGS': return changeFormSettings(state, action);
    case 'CHANGE_FORM_STYLES': return changeFormStyles(state, action);
    case 'REMOVE_NOTICE': return removeNotice(state, action);
    case 'SAVE_FORM_DONE': return saveFormDone(state);
    case 'SAVE_FORM_FAILED': return saveFormFailed(state, action);
    case 'SAVE_FORM_STARTED': return saveFormStarted(state);
    case 'SWITCH_SIDEBAR_TAB': return switchSidebarTab(state, action);
    case 'TOGGLE_SIDEBAR': return toggleSidebar(state, action);
    case 'TOGGLE_SIDEBAR_PANEL': return toggleSidebarPanel(state, action);
    default:
      return state;
  }
};
