import 'parsleyjs';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { initStore } from './store';
import { Settings } from './settings';

function Entry() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <Settings />
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('settings_container');
if (container) {
  initStore();
  ReactDOM.render(<Entry />, container);
}
