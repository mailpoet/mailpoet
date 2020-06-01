import React from 'react';
import MailPoet from 'mailpoet';
import { action } from '@storybook/addon-actions';
import Select from '../font_select';

export default {
  title: 'Form',
  component: Select,
};

MailPoet.I18n.add('formFontsDefaultTheme', 'Theme’s default fonts');
MailPoet.I18n.add('formFontsStandard', 'Standard fonts');
MailPoet.I18n.add('formFontsCustom', 'Custom fonts');

export const FontSelect = () => (
  <>
    <Select
      onChange={action('font selected')}
    />
  </>
);
