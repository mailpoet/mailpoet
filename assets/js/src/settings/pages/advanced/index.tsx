import React from 'react';
import { SaveButton } from 'settings/components';
import TaskScheduler from './task_scheduler';
import Roles from './roles';
import Tracking from './tracking';
import Transactional from './transactional';
import InactiveSubscribers from './inactive_subscribers';
import ShareData from './share_data';
import Captcha from './captcha';
import Reinstall from './reinstall';
import Logging from './logging';

export default function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <TaskScheduler />
      <Roles />
      <Tracking />
      <Transactional />
      <InactiveSubscribers />
      <ShareData />
      <Captcha />
      <Reinstall />
      <Logging />
      <SaveButton />
    </div>
  );
}
