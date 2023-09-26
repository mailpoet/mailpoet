import { EmailPanel } from './email_panel';
import { GoogleAnalyticsPanel } from './google_analytics_panel';
import { ReplyToPanel } from './reply_to_panel';

export function Edit(): JSX.Element {
  return (
    <>
      <EmailPanel />
      <ReplyToPanel />
      <GoogleAnalyticsPanel />
    </>
  );
}
