import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import { extractEmailDomain } from 'common/functions';
import { Tabs } from './tabs/tabs';
import { Tab } from './tabs/tab';
import { Modal } from './modal/modal';
import { AuthorizeSenderEmailModal } from './authorize_sender_email_modal';
import { AuthorizeSenderDomainModal } from './authorize_sender_domain_modal';

type Props = {
  onRequestClose: () => void;
  senderEmail: string;
  onSuccessAction: (param: { type: 'email' | 'domain'; data: string }) => void;
  showSenderEmailTab: boolean;
  showSenderDomainTab: boolean;
};

function AuthorizeSenderEmailAndDomainModal({
  onRequestClose,
  senderEmail,
  onSuccessAction,
  showSenderEmailTab = false,
  showSenderDomainTab = false,
}: Props): JSX.Element {
  if (!senderEmail) return null;

  const emailAddressDomain = extractEmailDomain(senderEmail);

  let defaultTab = 'sender_email';
  defaultTab =
    showSenderDomainTab && !showSenderEmailTab ? 'sender_domain' : defaultTab;

  return (
    <Modal
      onRequestClose={onRequestClose}
      contentClassName="authorize-sender-email-and-domain-modal"
    >
      <Tabs activeKey={defaultTab}>
        <Tab
          key="sender_email"
          className={classnames({
            mailpoet_hidden: !showSenderEmailTab,
          })}
          title={MailPoet.I18n.t(
            'authorizeSenderEmailAndDomainModalSenderEmailTabTitle',
          )}
        >
          {showSenderEmailTab && (
            <AuthorizeSenderEmailModal
              useModal={false}
              senderEmail={senderEmail}
              onRequestClose={onRequestClose}
              setAuthorizedAddress={(authorizedEmailAddress) => {
                onSuccessAction({
                  type: 'email',
                  data: authorizedEmailAddress,
                });
              }}
            />
          )}
        </Tab>
        <Tab
          key="sender_domain"
          className={classnames({
            mailpoet_hidden: !showSenderDomainTab,
          })}
          title={MailPoet.I18n.t(
            'authorizeSenderEmailAndDomainModalSenderDomainTabTitle',
          )}
        >
          {showSenderDomainTab && (
            <AuthorizeSenderDomainModal
              useModal={false}
              senderDomain={emailAddressDomain}
              onRequestClose={onRequestClose}
              setVerifiedSenderDomain={(verifiedSenderDomain) => {
                onSuccessAction({ type: 'domain', data: verifiedSenderDomain });
              }}
            />
          )}
        </Tab>
      </Tabs>
    </Modal>
  );
}

export { AuthorizeSenderEmailAndDomainModal };
