import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

export function ConsentDescription(): JSX.Element {
  return (
    <p className="description">
      {ReactStringReplace(
        MailPoet.I18n.t('consentSubscribed'),
        /\[link](.*?)\[\/link]/,
        (match) => (
          <a
            className="mailpoet-link"
            href="https://kb.mailpoet.com/article/357-why-express-consent-is-important"
            key="kb-link"
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ),
      )}
    </p>
  );
}
