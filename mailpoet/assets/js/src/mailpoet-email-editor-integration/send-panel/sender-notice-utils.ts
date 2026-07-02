import { extractEmailDomain, isEmail } from 'common/functions';
import { MailPoet } from 'mailpoet';
import { SenderRestrictionsType } from 'common/sender-domain-notice';
import { UseSenderFields } from '../shared/use-sender-fields';

export function isFreeMailSenderAddress(address: string): boolean {
  return (
    isEmail(address) &&
    MailPoet.freeMailDomains.indexOf(extractEmailDomain(address)) > -1
  );
}

// Resolves which sender-authorization warnings apply. These only matter for the
// MailPoet Sending Service when authorization is enforced. Free-mail domains are
// covered by FreeMailSenderNotice instead.
export function getSenderAuthorizationState(senderFields: UseSenderFields): {
  showEmailAuthorization: boolean;
  showDomainWarning: boolean;
} {
  const senderRestrictions: SenderRestrictionsType | undefined =
    window.mailpoet_sender_restrictions;
  const { senderAddress, isSenderAddressAuthorized, showSenderDomainWarning } =
    senderFields;

  const isEnforced =
    window.mailpoet_mss_active &&
    window.mailpoet_mta_method === 'MailPoet' &&
    !senderRestrictions?.skipAuthorization &&
    Boolean(senderAddress);

  return {
    showEmailAuthorization: isEnforced && !isSenderAddressAuthorized,
    showDomainWarning:
      isEnforced &&
      isSenderAddressAuthorized &&
      showSenderDomainWarning &&
      !isFreeMailSenderAddress(senderAddress),
  };
}

export function hasSenderAuthorizationIssue(
  senderFields: UseSenderFields,
): boolean {
  const { showEmailAuthorization, showDomainWarning } =
    getSenderAuthorizationState(senderFields);
  return showEmailAuthorization || showDomainWarning;
}
