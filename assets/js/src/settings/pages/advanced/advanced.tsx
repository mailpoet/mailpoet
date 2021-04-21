import React from 'react';
import { SaveButton } from 'settings/components';
import TaskScheduler from './task_scheduler';
import Roles from './roles';
import Tracking from './tracking';
import Transactional from './transactional';
import InactiveSubscribers from './inactive_subscribers';
import ShareData from './share_data';
import { Libs3rdParty } from './libs_3rd_party';
import Captcha from './captcha';
import Reinstall from './reinstall';
import { RecalculateSubscriberScore } from './recalculate_subscriber_score';
import Logging from './logging';
import BounceAddress from './bounce_address';

export default function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
      <Roles />
      <Tracking />
      <Transactional />
      <RecalculateSubscriberScore />
      <InactiveSubscribers />
      <ShareData />
      <Libs3rdParty />
      <Captcha />
      <Reinstall />
      <Logging />
      <SaveButton />
    </div>
  );
}
