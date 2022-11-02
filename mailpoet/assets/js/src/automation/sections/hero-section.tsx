import { __ } from '@wordpress/i18n';
import { Hooks } from '../../hooks';
import { OptionButton } from '../components/option-button';
import { MailPoet } from '../../mailpoet';

export function HeroSection(): JSX.Element {
  const buttonActions: JSX.Element[] = Hooks.applyFilters(
    'mailpoet.automation.hero.actions',
    [],
  );
  return (
    <section className="mailpoet-automation-section mailpoet-automation-section-hero">
      <div>
        <span className="mailpoet-automation-preheading">
          {__('Automations', 'mailpoet')}
        </span>
        <h1>{__('Better engagement begins with automation', 'mailpoet')}</h1>
        <p>
          {__(
            'Send emails that inform, reward, and engage your audience through powerful segmenting, scheduling, and design tools.',
            'mailpoet',
          )}
        </p>

        <OptionButton
          variant="primary"
          onClick={() => {
            window.location.href = MailPoet.urls.automationTemplates;
          }}
          title={__('Start with a template', 'mailpoet')}
        >
          {buttonActions}
        </OptionButton>
      </div>
      <img
        src={`${MailPoet.urls.imageAssets}automation/sections/hero.png`}
        alt={__('Welcome', 'mailpoet')}
      />
    </section>
  );
}
