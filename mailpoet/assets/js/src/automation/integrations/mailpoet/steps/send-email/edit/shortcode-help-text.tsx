import { __ } from '@wordpress/i18n';

export function ShortcodeHelpText(): JSX.Element {
  return (
    <span className="mailpoet-shortcode-selector">
      <a
        href="https://kb.mailpoet.com/article/215-personalize-newsletter-with-shortcodes"
        target="_blank"
        rel="noopener noreferrer"
      >
        {__('You can use MailPoet shortcodes.', 'mailpoet')}
      </a>
    </span>
  );
}
