import { SaveButton } from 'settings/components';
import { TaskScheduler } from './task-scheduler';
import { Roles } from './roles';
import { EngagementTracking } from './engagement-tracking';
import { HumanAndMachineOpens } from './human-and-machine-opens';
import { Transactional } from './transactional';
import { InactiveSubscribers } from './inactive-subscribers';
import { ShareData } from './share-data';
import { Libs3rdParty } from './libs-3rd-party';
import { Captcha } from './captcha';
import { Reinstall } from './reinstall';
import { RecalculateSubscriberScore } from './recalculate-subscriber-score';
import { Logging } from './logging';
import { BounceAddress } from './bounce-address';
import { CaptchaOnSignup } from './captcha-on-signup';

export function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
      <Roles />
      <EngagementTracking />
      <HumanAndMachineOpens />
      <Transactional />
      <RecalculateSubscriberScore />
      <InactiveSubscribers />
      <ShareData />
      <Libs3rdParty />
      <Captcha />
      <CaptchaOnSignup />
      <Reinstall />
      <Logging />
      <SaveButton />
    </div>
  );
}
