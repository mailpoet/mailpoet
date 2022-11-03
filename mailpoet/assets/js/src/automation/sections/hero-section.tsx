import { __ } from '@wordpress/i18n';
import { Hooks } from '../../hooks';
import { OptionButton } from '../components/option-button';
import { MailPoet } from '../../mailpoet';
import { StepMoreControlsType } from '../types/filters';

export function HeroSection(): JSX.Element {
  const buttonControls: StepMoreControlsType = Hooks.applyFilters(
    'mailpoet.automation.hero.actions',
    {},
  );
  return (
    <section className="mailpoet-automation-section mailpoet-automation-white-background">
      <div className="mailpoet-automation-section-content mailpoet-automation-section-hero">
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
            controls={buttonControls}
          />
        </div>
        <img
          src={`${MailPoet.cdnUrl}automation/sections/hero.png`}
          alt={__('Welcome', 'mailpoet')}
        />
      </div>
    </section>
  );
}
