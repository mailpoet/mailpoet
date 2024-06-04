import { BridgePingErrorNotice } from './bridge-ping-error-notice';
import { CronPingErrorNotice } from './cron-ping-error-notice';

function GlobalNotices() {
  return (
    <>
      <BridgePingErrorNotice />
      <CronPingErrorNotice />
    </>
  );
}

GlobalNotices.displayName = 'GlobalNotices';
export { GlobalNotices };
