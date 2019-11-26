import addNotice from './reducers/addNotice.jsx';
import toggleSidebar from './reducers/toggleSidebar.jsx';
import changeFormName from './reducers/changeFormName.jsx';
import changeFormStyles from './reducers/changeFormStyles.jsx';
import saveFormStarted from './reducers/saveFormStarted.jsx';
import saveFormDone from './reducers/saveFormDone.jsx';
import removeNotice from './reducers/removeNotice.jsx';
import changeFormSettings from './reducers/changeFormSettings.jsx';
import switchSidebarTab from './reducers/switchSidebarTab.jsx';
import toggleSidebarPanel from './reducers/toggleSidebarPanel.jsx';

export default (defaultState) => (state = defaultState, action) => {
  switch (action.type) {
    case 'ADD_NOTICE': return addNotice(state, action);
    case 'CHANGE_FORM_NAME': return changeFormName(state, action);
    case 'CHANGE_FORM_STYLES': return changeFormStyles(state, action);
    case 'REMOVE_NOTICE': return removeNotice(state, action);
    case 'SAVE_FORM_DONE': return saveFormDone(state);
    case 'SAVE_FORM_STARTED': return saveFormStarted(state);
    case 'TOGGLE_SIDEBAR': return toggleSidebar(state, action);
    case 'CHANGE_FORM_SETTINGS': return changeFormSettings(state, action);
    case 'SWITCH_SIDEBAR_TAB': return switchSidebarTab(state, action);
    case 'TOGGLE_SIDEBAR_PANEL': return toggleSidebarPanel(state, action);
    default:
      return state;
  }
};
