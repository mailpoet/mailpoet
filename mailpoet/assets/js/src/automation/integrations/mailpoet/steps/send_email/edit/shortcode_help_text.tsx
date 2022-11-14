import { __ } from '@wordpress/i18n';

export function ShortcodeHelpText(): JSX.Element {
  return (
    <span className="mailpoet-shortcode-selector">
      You can use{' '}
      <a
        href="https://kb.mailpoet.com/article/215-personalize-newsletter-with-shortcodes"
        target="_blank"
        rel="noopener noreferrer"
      >
        {__('MailPoet shortcodes', 'mailpoet')}
      </a>
    </span>
  );
}
