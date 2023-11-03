import 'parsleyjs';
import { createRoot } from 'react-dom/client';
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
  initStore();
  const root = createRoot(container);
  root.render(<Entry />);
}
