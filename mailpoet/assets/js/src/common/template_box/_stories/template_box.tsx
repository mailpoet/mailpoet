import React from 'react';
import MailPoet from 'mailpoet';
import { action } from '@storybook/addon-actions';
import TemplateBox from '../template_box';

export default {
  title: 'Template Box',
  component: TemplateBox,
};

MailPoet.I18n.add('select', 'Select');
MailPoet.I18n.add('delete', 'Delete');

export const TemplateBoxWithThumbnail = () => (
  <div className="mailpoet-templates">
    <TemplateBox
      onSelect={action('Select')}
      label="Kitten"
      automationId="kittens-id"
    >
      <div className="mailpoet-template-thumbnail">
        <img src="http://placekitten.com/300/350" alt="Kittens" />
      </div>
    </TemplateBox>
    <TemplateBox
      onSelect={action('Select')}
      onDelete={action('Delete')}
      label="Cuter kitten"
      automationId="kitten-cute-id"
    >
      <div className="mailpoet-template-thumbnail">
        <img src="http://placekitten.com/300/350" alt="Kittens" />
      </div>
    </TemplateBox>
  </div>
);
