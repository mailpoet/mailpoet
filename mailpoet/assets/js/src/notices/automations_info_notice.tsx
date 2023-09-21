import ReactStringReplace from 'react-string-replace';
import { MailPoet } from 'mailpoet';
import { Notice } from 'notices/notice';

function AutomationsInfoNotice() {
  if (!MailPoet.hideAutomations) return null;
  let automationsInfo = ReactStringReplace(
    MailPoet.I18n.t('automationsInfoNotice'),
    /\[link1\](.*?)\[\/link1\]/g,
    (match) => (
      <a
        key={match}
        rel="noreferrer"
        href="https://kb.mailpoet.com/article/397-how-to-set-up-an-automation"
        target="_blank"
      >
        {match}
      </a>
    ),
  );
  automationsInfo = ReactStringReplace(
    automationsInfo,
    /\[link2\](.*?)\[\/link2\]/g,
    (match) => (
      <a
        key={match}
        rel="noreferrer"
        href="https://href.li/?https://kb.mailpoet.com/article/408-integration-with-automatewoo"
        target="_blank"
      >
        {match}
      </a>
    ),
  );
  return (
    <Notice type="warning" scroll renderInPlace timeout={false}>
      <p>{automationsInfo}</p>
    </Notice>
  );
}

export { AutomationsInfoNotice };
