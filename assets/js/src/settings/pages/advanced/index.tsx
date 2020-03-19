import React from 'react';
import BounceAddress from './bounce_address';
import TaskScheduler from './task_scheduler';
import Roles from './roles';

export default function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
      <Roles />
    </div>
  );
}
