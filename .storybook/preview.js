import React from 'react';
import { addDecorator } from '@storybook/react';
import { withPerformance } from 'storybook-addon-performance';
import '../assets/css/src/storybook/wordpress-buttons-5.7.2.css';
import '../assets/dist/css/mailpoet-plugin.css';
import '../assets/dist/css/mailpoet-form-editor.css';

addDecorator(withPerformance);
addDecorator(story => <div class="wp-core-ui" id="wpbody"><div id="mailpoet-modal"></div>{story()}</div>);
