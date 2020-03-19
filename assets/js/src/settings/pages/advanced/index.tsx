import React from 'react';
import BounceAddress from './bounce_address';
import TaskScheduler from './task_scheduler';

export default function Advanced() {
  return (
    <div className="mailpoet-settings-grid">
      <BounceAddress />
      <TaskScheduler />
    </div>
  );
}
