import { __ } from '@wordpress/i18n';

export function Onboarding(): JSX.Element {
  return (
    <div className="mailpoet-automation onboarding">
      <img src="" alt="" />
      <h1>{__('Scale your business with advanced automations', 'mailpoet')}</h1>
      <p>
        {__(
          'Automated workflow allows you to set up a chain of interactions with your subscribers with less efforts. You control all the flow with a visual scheme and a set of goals. Try it!',
          'mailpoet',
        )}
      </p>
      <a href="#" className="button secondary">
        {__('Learn more', 'mailpoet')}
      </a>
    </div>
  );
}
