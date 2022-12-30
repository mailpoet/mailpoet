import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { Heading } from 'common/typography/heading/heading';

function Faq() {
  return (
    <div className="landing-faq mailpoet-content-padding">
      <div className="mailpoet-content-center">
        <Heading level={2}> {MailPoet.I18n.t('faqHeader')} </Heading>
        <p>
          {ReactStringReplace(
            MailPoet.I18n.t('faqHeaderSubText'),
            /\[link\](.*?)\[\/link\]/,
            (text) => (
              <a
                key={text}
                href="https://kb.mailpoet.com/"
                rel="noopener noreferrer"
                target="_blank"
              >
                {text}
              </a>
            ),
          )}
        </p>
      </div>

      <p> FAQ here. Replace with FAQ </p>
    </div>
  );
}
Faq.displayName = 'Landingpage FAQ';

export { Faq };
