import { createRoot } from 'react-dom/client';

import { ErrorBoundary } from 'common';
import { List } from './list';

declare global {
  interface Window {
    mailpoet_logs_default_from: string;
  }
}

const container = document.getElementById('mailpoet_logs_container');

if (container) {
  const root = createRoot(container);
  root.render(
    <ErrorBoundary>
      <List defaultFrom={window.mailpoet_logs_default_from} />
    </ErrorBoundary>,
  );
}
