import { SaveButton } from 'settings/components';
import { useSetting } from 'settings/store/hooks';
import { TaskScheduler } from './task-scheduler';
import { Roles } from './roles';
import { EngagementTracking } from './engagement-tracking';
import { TrackUnknownConsent } from './track-unknown-consent';
import { HumanAndMachineOpens } from './human-and-machine-opens';
import { Transactional } from './transactional';
import { InactiveSubscribers } from './inactive-subscribers';
import { SendingStatusRetention } from './sending-status-retention';
import { SendingQueueBodyCleanup } from './sending-queue-body-cleanup';
import { SubscriberTimeZoneData } from './subscriber-time-zone-data';
import { EmailSharingVisibility } from './email-sharing-visibility';
import { ShareData } from './share-data';
import { Libs3rdParty } from './libs-3rd-party';
import { Captcha } from './captcha';
import { Reinstall } from './reinstall';
import { RecalculateSubscriberScore } from './recalculate-subscriber-score';
import { Logging } from './logging';
import { BounceAddress } from './bounce-address';
import { CaptchaOnSignup } from './captcha-on-signup';
import { CaptchaPage } from './captcha-page';

export function Advanced() {
  const [captchaType] = useSetting('captcha', 'type');

  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
      <Roles />
      <EngagementTracking />
      <TrackUnknownConsent />
      <HumanAndMachineOpens />
      <Transactional />
      <RecalculateSubscriberScore />
      <InactiveSubscribers />
      <SendingStatusRetention />
      <SendingQueueBodyCleanup />
      <SubscriberTimeZoneData />
      <EmailSharingVisibility />
      <ShareData />
      <Libs3rdParty />
      <Captcha />
      {captchaType === 'built-in' && <CaptchaPage />}
      <CaptchaOnSignup />
      <Reinstall />
      <Logging />
      <SaveButton />
    </div>
  );
}
