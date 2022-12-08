import { Notices } from 'notices/notices';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice';

export function HomepageNotices(): JSX.Element {
  return (
    <>
      <Notices />
      <SubscribersLimitNotice />
    </>
  );
}
