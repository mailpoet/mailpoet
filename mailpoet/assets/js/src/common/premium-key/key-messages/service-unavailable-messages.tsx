import { __ } from '@wordpress/i18n';

export function ServiceUnavailableMessage() {
  return (
    <div className="mailpoet_error_item mailpoet_error">
      {__('Yikes, we can’t validate your key because:')}
      <ul className="disc-inside-list">
        <li>
          {__(
            'You’re on localhost or using an IP address instead of a domain. Not allowed for security reasons!',
            'mailpoet',
          )}
        </li>
        <li>
          {__(
            'Your host is blocking the activation, e.g. Altervista',
            'mailpoet',
          )}
        </li>
        <li>
          {__(
            'This website is on an Intranet. Activating MailPoet will not be possible.',
            'mailpoet',
          )}
        </li>
      </ul>
      <p>
        <a
          href="https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key"
          target="_blank"
          rel="noopener noreferrer"
          className="mailpoet_error"
        >
          {__('Learn more', 'mailpoet')}
        </a>
      </p>
    </div>
  );
}
