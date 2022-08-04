import { MailPoet } from 'mailpoet';
import { extractEmailDomain } from 'common/functions';

/**
 * @param {string} email - Email address
 * @param {ApiActionType} type - action type
 * @returns {Promise}
 */
const makeApiRequest = (domain: string) =>
  MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'settings',
    action: 'checkDomainDmarcPolicy',
    data: { domain },
  });

/**
 * Check domain DMARC policy
 *
 * returns `false` if not required, `true` if DMARC policy is Restricted
 * @param {string} email Email address
 * @param {boolean} isMssActive Is MailPoet sending service active?
 * @returns {Promise<boolean>} false if not required, `true` if DMARC policy is Restricted
 */
const checkSenderEmailDomainDmarcPolicy = async (
  email: string,
  isMssActive = window.mailpoet_mss_active,
) => {
  if (!email) return false;

  if (!isMssActive) {
    return false;
  }
  const emailAddressDomain = extractEmailDomain(email);

  const isDomainVerified = (
    window.mailpoet_verified_sender_domains || []
  ).includes(emailAddressDomain);
  if (isDomainVerified) {
    // do nothing if the email domain is verified
    return false;
  }

  try {
    const res = await makeApiRequest(emailAddressDomain);
    const isDmarcPolicyRestricted = Boolean(res?.data?.isDmarcPolicyRestricted);
    return isDmarcPolicyRestricted;
  } catch (error) {
    // do nothing for now when the request fails
    return false;
  }
};

export { checkSenderEmailDomainDmarcPolicy };
