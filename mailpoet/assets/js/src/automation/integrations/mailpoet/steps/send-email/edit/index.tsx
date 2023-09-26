import { EmailPanel } from './email-panel';
import { GoogleAnalyticsPanel } from './google-analytics-panel';
import { ReplyToPanel } from './reply-to-panel';

export function Edit(): JSX.Element {
  return (
    <>
      <EmailPanel />
      <ReplyToPanel />
      <GoogleAnalyticsPanel />
    </>
  );
}
