import 'parsleyjs';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { initStore } from './store';
import { Settings } from './settings';
import { registerTranslations } from '../common';

function Entry() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <Settings />
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('settings_container');
if (container) {
  registerTranslations();
  initStore(window);
  ReactDOM.render(<Entry />, container);
}
