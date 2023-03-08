import { StrictMode } from 'react';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { Selection } from './templates/selection';
import { createStore } from './templates/store/store';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <>
        <Notices />
        <Selection />
      </>
    </GlobalContext.Provider>
  );
}

const appElement = document.querySelector('#mailpoet_form_edit_templates');
if (appElement) {
  createStore();
  ReactDOM.render(
    <StrictMode>
      <App />
    </StrictMode>,
    appElement,
  );
}
