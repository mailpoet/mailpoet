import { createRoot } from 'react-dom/client';

import { ErrorBoundary } from 'common';
import { FilterType, List, Logs } from './list';

interface LogsWindow extends Window {
  mailpoet_logs: Logs;
}

declare let window: LogsWindow;

const container = document.getElementById('mailpoet_logs_container');

if (container) {
  const url = new URL(window.location.href);

  const root = createRoot(container);
  root.render(
    <ErrorBoundary>
      <List
        logs={window.mailpoet_logs}
        originalFrom={url.searchParams.get('from')}
        originalTo={url.searchParams.get('to')}
        originalSearch={url.searchParams.get('search')}
        originalOffset={url.searchParams.get('offset')}
        originalLimit={url.searchParams.get('limit')}
        onFilter={(data: FilterType): void => {
          url.searchParams.delete('from');
          url.searchParams.delete('to');
          url.searchParams.delete('search');
          url.searchParams.delete('offset');
          url.searchParams.delete('limit');
          Object.entries(data).forEach(([key, value]) => {
            url.searchParams.append(key, value);
          });
          window.location.href = url.href;
        }}
      />
    </ErrorBoundary>,
  );
}
