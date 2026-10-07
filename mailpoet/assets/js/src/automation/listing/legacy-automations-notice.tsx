import { useCallback } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice } from 'notices/notice';
import { legacyApiFetch } from './store/legacy-api';

export function LegacyAutomationsNotice(): JSX.Element {
  const saveNoticeDismissed = useCallback(() => {
    void legacyApiFetch({
      endpoint: 'UserFlags',
      method: 'set',
      'data[legacy_automations_notice_dismissed]': '1',
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
            'Your existing automations are now listed here. You can also create new, more powerful automations with our new Automations editor. <link>Learn more</link>',
            'mailpoet',
          ),
          {
            link: (
              // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
              <a
                href="https://kb.mailpoet.com/article/397-how-to-set-up-an-automation"
                target="_blank"
                rel="noopener noreferrer"
              />
            ),
          },
        )}
      </p>
    </Notice>
  );
}
