import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { PremiumModal } from '../../../../common/premium-modal';

export function FooterCredit({ logoSrc }: { logoSrc: string }) {
  const [isModalOpened, setIsModalOpened] = useState(false);

  return (
    <>
      <button
        type="button"
        className="mailpoet-email-footer-credit"
        onClick={() => setIsModalOpened(true)}
      >
        <img src={logoSrc} alt="MailPoet" />
      </button>
      {!!isModalOpened && (
        <PremiumModal onRequestClose={() => setIsModalOpened(false)}>
          {__(
            'A MailPoet logo will appear in the footer of all emails sent with the free version of MailPoet.',
            'mailpoet',
          )}
        </PremiumModal>
      )}
    </>
  );
}

export default FooterCredit;
