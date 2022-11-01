import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { MailPoet } from '../../mailpoet';
import { workflowTemplates } from '../templates/config';
import { TemplateListItem } from '../templates/components/template-list-item';

export function TemplatesSection(): JSX.Element {
  const templates = workflowTemplates.slice(0, 3);

  return (
    <section className="mailpoet-automation-section mailpoet-section-templates">
      <h2>{__('Explore essentials', 'mailpoet')}</h2>
      <p>
        {__(
          'Choose from our list of pre-made templates and make it your own.',
          'mailpoet',
        )}
      </p>
      <ul className="mailpoet-section-template-list">
        {templates.map((template) => (
          <TemplateListItem
            key={template.slug}
            template={template}
            heading="h3"
          />
        ))}
      </ul>
      <Button variant="link" href={MailPoet.urls.automationTemplates}>
        {__('Browse all templates →', 'mailpoet')}
      </Button>
    </section>
  );
}
