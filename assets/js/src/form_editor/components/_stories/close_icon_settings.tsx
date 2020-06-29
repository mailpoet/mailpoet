import React from 'react';
import { action } from '@storybook/addon-actions';
import CloseButtonSettings from '../close_button_settings';

export default {
  title: 'FormEditor/Close Icon Settings',
};

export const CloseIconSettings = () => (
  <div>
    <CloseButtonSettings
      name="Select"
      onChange={action('on change')}
    />
  </div>
);
