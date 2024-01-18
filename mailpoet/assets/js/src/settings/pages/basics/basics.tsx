import { useParams } from 'react-router-dom';
import { SaveButton } from 'settings/components';
import { t } from 'common/functions';
import { DefaultSender } from './default-sender';
import { SubscribeOn } from './subscribe-on';
import { ManageSubscription } from './manage-subscription';
import { UnsubscribePage } from './unsubscribe-page';
import { StatsNotifications } from './stats-notifications';
import { NewSubscriberNotifications } from './new-subscriber-notifications';
import { Shortcode } from './shortcode';
import { GdprCompliant } from './gdpr-compliant';
import { ReEngagementPage } from './re-engagement-page';

export function Basics() {
  const { showModal } = useParams<{ showModal: string }>();
  return (
    <div className="mailpoet-settings-grid">
      <DefaultSender showModal={showModal} />
      <SubscribeOn
        event="on_comment"
        title={t('subscribeInCommentsTitle')}
        description={t('subscribeInCommentsDescription')}
      />
      <SubscribeOn
        event="on_register"
        title={t('subscribeInRegistrationTitle')}
        description={t('subscribeInRegistrationDescription')}
      />
      <ManageSubscription />
      <UnsubscribePage />
      <ReEngagementPage />
      <StatsNotifications />
      <NewSubscriberNotifications />
      <Shortcode
        name="mailpoet_archive"
        title={t('archiveShortcodeTitle')}
        description={t('archiveShortcodeDescription')}
      />
      <Shortcode
        name="mailpoet_subscribers_count"
        title={t('subscribersCountShortcodeTitle')}
        description={t('subscribersCountShortcodeDescription')}
      />
      <GdprCompliant />
      <SaveButton />
    </div>
  );
}
