import { useCallback } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice } from './notice';
import { legacyApiFetch } from '../automation/listing/store/legacy-api';

export function LegacyAutomaticEmailsNotice(): JSX.Element {
  const saveNoticeDismissed = useCallback(() => {
    void legacyApiFetch({
      endpoint: 'UserFlags',
      method: 'set',
      'data[legacy_automatic_emails_notice_dismissed]': '1',
    });
  }, []);

  return (
    <Notice
      type="info"
      timeout={false}
      closable
      renderInPlace
      onClose={saveNoticeDismissed}
    >
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
