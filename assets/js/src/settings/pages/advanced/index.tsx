import React from 'react';
import BounceAddress from './bounce_address';
import TaskScheduler from './task_scheduler';
import Roles from './roles';
import Tracking from './tracking';
import Transactional from './transactional';
import InactiveSubscribers from './inactive_subscribers';
import ShareData from './share_data';
import Captcha from './captcha';
import Reinstall from './reinstall';

export default function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
      <Roles />
      <Tracking />
      <Transactional />
      <InactiveSubscribers />
      <ShareData />
      <Captcha />
      <Reinstall />
    </div>
  );
}
