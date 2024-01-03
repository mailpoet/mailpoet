import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice } from 'notices/notice';

export function LegacyAutomationsNotice(): JSX.Element {
  return (
    <Notice type="info" timeout={false} closable renderInPlace>
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
