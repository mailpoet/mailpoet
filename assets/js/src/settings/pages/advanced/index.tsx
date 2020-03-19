import React from 'react';
import BounceAddress from './bounce_address';
import TaskScheduler from './task_scheduler';
import Roles from './roles';
import Tracking from './tracking';

export default function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
      <Roles />
      <Tracking />
    </div>
  );
}
