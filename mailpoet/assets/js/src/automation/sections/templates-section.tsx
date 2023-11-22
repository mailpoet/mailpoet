import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { MailPoet } from '../../mailpoet';
import { automationTemplates } from '../templates/config';
import { TemplatesGrid } from '../templates/components/templates-grid';

export function TemplatesSection(): JSX.Element {
  const templates = automationTemplates.slice(0, 3);

  return (
    <section className="mailpoet-automation-section">
      <div className="mailpoet-automation-section-content mailpoet-section-templates">
        <h2>{__('Explore essentials', 'mailpoet')}</h2>
        <p>
          {__(
            'Choose from our list of pre-made templates and make it your own.',
            'mailpoet',
          )}
        </p>
        <ul className="mailpoet-section-template-list">
          <TemplatesGrid templates={templates} />
        </ul>
        <Button variant="link" href={MailPoet.urls.automationTemplates}>
          {__('Browse all templates →', 'mailpoet')}
        </Button>
      </div>
    </section>
  );
}
