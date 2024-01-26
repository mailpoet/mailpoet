import { useMemo } from 'react';
import { extractEmailDomain } from 'common/functions';
import { SenderEmailAddressWarning } from '../../../../../../common/sender-email-address-warning';
import { MailPoet } from '../../../../../../mailpoet';
import { useSelectContext, updateSenderDomainsConfig } from '../../../context';

type SenderDomainInlineNoticeProps = {
  email: string;
};

/**
 * SenderDomainStatusNotice is a wrapper for SenderEmailAddressWarning to use with Automations.
 * It populates the props (mostly) with data from the context, instead of the Window object.
 * It still uses the window object for values that are present in all MailPoet pages
 * like mailpoet_mss_active or subscribers_count further down the tree.
 *
 * It updates the context when the user authorizes the email or domain
 * so that other email steps in the automation have an updated configuration.
 *
 * @param email - FROM address email
 * @returns JSX.Element
 */
function SenderDomainStatusNotice({
  email,
}: SenderDomainInlineNoticeProps): JSX.Element {
  const { senderDomainsConfig } = useSelectContext();

  const domain = extractEmailDomain(email);
  const isPartiallyVerifiedDomain =
    senderDomainsConfig.partiallyVerifiedSenderDomains.includes(domain);
  const isAuthorized =
    senderDomainsConfig.authorizedEmails.includes(email) ||
    senderDomainsConfig.verifiedSenderDomains.includes(domain);
  const showSenderDomainWarning =
    !senderDomainsConfig.verifiedSenderDomains.includes(domain);

  return (
    <SenderEmailAddressWarning
      emailAddress={email}
      mssActive={window.mailpoet_mss_active}
      isEmailAuthorized={isAuthorized}
      showSenderDomainWarning={showSenderDomainWarning && isAuthorized}
      isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
      senderRestrictions={senderDomainsConfig.senderRestrictions}
      onSuccessfulEmailOrDomainAuthorization={(data) => {
        if (data.type === 'email') {
          senderDomainsConfig.authorizedEmails.push(email);
          updateSenderDomainsConfig({ ...senderDomainsConfig });
          MailPoet.trackEvent('MSS in plugin authorize email', {
            'authorized email source': 'Automations',
            wasSuccessful: 'yes',
          });
        }
        if (data.type === 'domain') {
          senderDomainsConfig.verifiedSenderDomains.push(domain);
          senderDomainsConfig.partiallyVerifiedSenderDomains =
            senderDomainsConfig.partiallyVerifiedSenderDomains.filter(
              (item) => item !== domain,
            );
          if (!senderDomainsConfig.allSenderDomains.includes(domain)) {
            senderDomainsConfig.allSenderDomains.push(domain);
          }
          updateSenderDomainsConfig({ ...senderDomainsConfig });
          MailPoet.trackEvent('MSS in plugin verify sender domain', {
            'verify sender domain source': 'Automations',
            wasSuccessful: 'yes',
          });
        }
      }}
    />
  );
}

export function SenderDomainNotice({
  email,
}: SenderDomainInlineNoticeProps): JSX.Element {
  return useMemo(() => <SenderDomainStatusNotice email={email} />, [email]);
}
