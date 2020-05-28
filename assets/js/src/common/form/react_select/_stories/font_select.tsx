import React from 'react';
import { action } from '@storybook/addon-actions';
import Select from '../font_select';

export default {
  title: 'Form',
  component: Select,
};

export const FontSelect = () => (
  <>
    <Select
      onChange={action('font selected')}
    />
  </>
);
