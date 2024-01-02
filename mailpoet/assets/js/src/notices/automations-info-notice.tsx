import { createInterpolateElement } from '@wordpress/element';
import { MailPoet } from 'mailpoet';
import { Notice } from 'notices/notice';

function AutomationsInfoNotice() {
  const automationsInfo = createInterpolateElement(
    MailPoet.I18n.t('automationsInfoNotice'),
    {
      link1: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          rel="noreferrer"
          href="https://kb.mailpoet.com/article/397-how-to-set-up-an-automation"
          target="_blank"
        />
      ),
      link2: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          rel="noreferrer"
          href="https://kb.mailpoet.com/article/408-integration-with-automatewoo"
          target="_blank"
        />
      ),
    },
  );
  return (
    <Notice type="warning" scroll renderInPlace timeout={false}>
      <p>{automationsInfo}</p>
    </Notice>
  );
}

export { AutomationsInfoNotice };
