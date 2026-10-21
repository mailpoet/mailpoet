import { ErrorBoundary } from 'common';
import { List } from 'logs/list';

export function Logs(): JSX.Element {
  return (
    <ErrorBoundary>
      <List
        pageSlug="mailpoet-help"
        defaultFrom={window.mailpoet_logs_default_from}
        downloadConfig={window.mailpoet_logs_download}
      />
    </ErrorBoundary>
  );
}
