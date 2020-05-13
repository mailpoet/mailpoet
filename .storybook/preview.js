import React from 'react';
import { addDecorator } from '@storybook/react';
import { withPerformance } from 'storybook-addon-performance';
import '../assets/dist/css/mailpoet-plugin.css';
import '../assets/dist/css/mailpoet-form-editor.css';

addDecorator(withPerformance);
addDecorator(story => <div id="wpbody" style={{fontFamily:'sans-serif'}}>{story()}</div>);
