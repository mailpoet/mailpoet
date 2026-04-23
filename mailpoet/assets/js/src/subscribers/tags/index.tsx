import { createRoot } from 'react-dom/client';
import { GlobalContext, useGlobalContextValue } from 'context';
import { registerTranslations, ErrorBoundary } from 'common';
import { TagsPage } from './tags-page';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <ErrorBoundary>
        <TagsPage />
      </ErrorBoundary>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet_tags_container');
if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
