import { createRoot } from 'react-dom/client';
import { GlobalContext, useGlobalContextValue } from 'context';
import { registerTranslations, ErrorBoundary } from 'common';
import { EmailTemplatesPage } from './email-templates-page';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <ErrorBoundary>
        <EmailTemplatesPage />
      </ErrorBoundary>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet_email_templates_container');
if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
