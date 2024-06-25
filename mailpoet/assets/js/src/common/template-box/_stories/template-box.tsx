import { MailPoet } from 'mailpoet';
// @ts-expect-error - We have removed Storybook from project but we want to keep stories
import { action } from '@storybook/addon-actions';
import { TemplateBox } from '../template-box';

export default {
  title: 'Template Box',
  component: TemplateBox,
};

MailPoet.I18n.add('select', 'Select');
MailPoet.I18n.add('delete', 'Delete');

export function TemplateBoxWithThumbnail() {
  return (
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
}
