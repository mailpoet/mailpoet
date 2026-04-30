import { createRoot } from 'react-dom/client';
import { GlobalContext, useGlobalContextValue } from 'context';
import { registerTranslations, ErrorBoundary } from 'common';
import { CustomFieldsPage } from './custom-fields-page';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <ErrorBoundary>
        <CustomFieldsPage />
      </ErrorBoundary>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet_custom_fields_container');
if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
