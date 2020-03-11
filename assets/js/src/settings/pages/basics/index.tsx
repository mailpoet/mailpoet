import React from 'react';
import { SaveButton } from 'settings/components';
import DefaultSender from './default_sender';

export default function Basics() {
  return (
    <div className="mailpoet-settings-grid">
      <DefaultSender />
      <SaveButton />
    </div>
  );
}
