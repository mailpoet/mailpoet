import addNotice from './reducers/addNotice.jsx';
import toggleSidebar from './reducers/toggleSidebar.jsx';
import changeFormName from './reducers/changeFormName.jsx';
import saveFormStarted from './reducers/saveFormStarted.jsx';
import saveFormDone from './reducers/saveFormDone.jsx';
import removeNotice from './reducers/removeNotice.jsx';

export default (defaultState) => (state = defaultState, action) => {
  switch (action.type) {
    case 'ADD_NOTICE': return addNotice(state, action);
    case 'CHANGE_FORM_NAME': return changeFormName(state, action);
    case 'REMOVE_NOTICE': return removeNotice(state, action);
    case 'SAVE_FORM_DONE': return saveFormDone(state);
    case 'SAVE_FORM_STARTED': return saveFormStarted(state);
    case 'TOGGLE_SIDEBAR': return toggleSidebar(state, action);
    default:
      return state;
  }
};
