import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
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

  let showSenderEmail = showSenderEmailTab;
  let showSenderDomain = showSenderDomainTab;

  const updateProperty = (prop: boolean, type: 'email' | 'domain') => {
    if (type === 'email') showSenderEmail = prop;
    if (type === 'domain') showSenderDomain = prop;
  };

  /**
   *  we want to make sure we have at lease one tab avaliable
   *  if not, close this modal
   * @param prop Property
   * @param type action type
   */
  const updateDisplayedTab = (prop: boolean, type: 'email' | 'domain') => {
    if (type === 'email' && prop === false && showSenderDomain === false) {
      // domain is currently hidden, trying to hide email, close modal
      onRequestClose();
    }
    if (type === 'domain' && prop === false && showSenderEmail === false) {
      // email is currently hidden, trying to hide sender domain, close modal
      onRequestClose();
    }
    updateProperty(prop, type);
  };

  const emailAddressDomain = senderEmail.split('@').pop().toLowerCase();

  if (showSenderEmailTab === false && showSenderDomainTab === false) {
    // show sender_email by default
    updateDisplayedTab(true, 'email');
  }

  const defaultTab = showSenderEmail ? 'sender_email' : 'sender_domain';

  return (
    <Modal
      onRequestClose={onRequestClose}
      contentClassName="authorize-sender-email-and-domain-modal"
    >
      <Tabs activeKey={defaultTab}>
        <Tab
          key="sender_email"
          className={classnames({
            mailpoet_hidden: !showSenderEmail,
          })}
          title={MailPoet.I18n.t(
            'authorizeSenderEmailAndDomainModalSenderEmailTabTitle',
          )}
        >
          {showSenderEmail && (
            <AuthorizeSenderEmailModal
              useModal={false}
              senderEmail={senderEmail}
              onRequestClose={onRequestClose}
              setAuthorizedAddress={(authorizedEmailAddress) => {
                onSuccessAction({
                  type: 'email',
                  data: authorizedEmailAddress,
                });
                updateDisplayedTab(false, 'email');
              }}
            />
          )}
        </Tab>
        <Tab
          key="sender_domain"
          className={classnames({
            mailpoet_hidden: !showSenderDomain,
          })}
          title={MailPoet.I18n.t(
            'authorizeSenderEmailAndDomainModalSenderDomainTabTitle',
          )}
        >
          {showSenderDomain && (
            <AuthorizeSenderDomainModal
              useModal={false}
              senderDomain={emailAddressDomain}
              onRequestClose={onRequestClose}
              setVerifiedSenderDomain={(verifiedSenderDomain) => {
                onSuccessAction({ type: 'domain', data: verifiedSenderDomain });
                updateDisplayedTab(false, 'domain');
              }}
            />
          )}
        </Tab>
      </Tabs>
    </Modal>
  );
}

export { AuthorizeSenderEmailAndDomainModal };
