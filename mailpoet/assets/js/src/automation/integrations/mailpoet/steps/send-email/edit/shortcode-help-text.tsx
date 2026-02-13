import { __ } from '@wordpress/i18n';

const isGarden =
  (window as { mailpoet_automation_context?: { is_garden?: boolean } })
    .mailpoet_automation_context?.is_garden === true;

export function ShortcodeHelpText(): JSX.Element {
  if (isGarden) {
    return (
      <span className="mailpoet-shortcode-selector">
        {__('You can use shortcodes.', 'mailpoet')}
      </span>
    );
  }
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
