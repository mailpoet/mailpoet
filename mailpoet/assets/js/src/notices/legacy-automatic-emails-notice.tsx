import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice } from './notice';

export function LegacyAutomaticEmailsNotice(): JSX.Element {
  return (
    <Notice type="info" timeout={false} closable={false} renderInPlace>
      <p>
        {createInterpolateElement(
          __(
            'We moved your existing Welcome and WooCommerce emails to Automations. Rest assured, all your automations are still actively running. <link>View automations</link>',
            'mailpoet',
          ),
          {
            // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
            link: <a href="admin.php?page=mailpoet-automation" />,
          },
        )}
      </p>
    </Notice>
  );
}
