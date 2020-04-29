import React from 'react';
import { addDecorator } from '@storybook/react';
import { withPerformance } from 'storybook-addon-performance';
import '../assets/dist/css/mailpoet-plugin.css';

addDecorator(withPerformance);
addDecorator(story => <div id="wpcontent">{story()}</div>);
