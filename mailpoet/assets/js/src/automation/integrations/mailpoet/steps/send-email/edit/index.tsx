import { useSelect } from '@wordpress/data';
import { EmailPanel } from './email-panel';
import { GoogleAnalyticsPanel } from './google-analytics-panel';
import { ReplyToPanel } from './reply-to-panel';
import { storeName } from '../../../../../editor/store';

export function Edit(): JSX.Element {
  const isGarden = useSelect(
    (select) => select(storeName).getContext('is_garden') === true,
    [],
  );
  return (
    <>
      <EmailPanel />
      {!isGarden && <ReplyToPanel />}
      <GoogleAnalyticsPanel />
    </>
  );
}
