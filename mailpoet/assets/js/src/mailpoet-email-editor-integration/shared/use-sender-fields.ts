import { __ } from '@wordpress/i18n';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { MailPoet } from 'mailpoet';
import { extractEmailDomain, isEmail } from 'common/functions';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

type MailPoetEmailData = {
  type?: string;
  sender_name?: string;
  sender_address?: string;
  reply_to_name?: string;
  reply_to_address?: string;
};

type AjaxStringListResponse = {
  data?: string[];
};

type AuthorizationSuccessData = {
  type: 'email' | 'domain';
  data: string;
};

export type UseSenderFields = {
  senderName: string;
  senderAddress: string;
  replyToName: string;
  replyToAddress: string;
  senderNameError: string;
  senderAddressError: string;
  replyToAddressError: string;
  hasValidationErrors: boolean;
  isSenderAddressAuthorized: boolean;
  showSenderDomainWarning: boolean;
  isPartiallyVerifiedDomain: boolean;
  setSenderName: (value: string) => void;
  setSenderAddress: (value: string) => void;
  setReplyToName: (value: string) => void;
  setReplyToAddress: (value: string) => void;
  validateSenderFields: () => Promise<boolean>;
  validateSenderAddress: () => void;
  validateReplyToAddress: () => void;
  handleSuccessfulAuthorization: (data: AuthorizationSuccessData) => void;
};

function updateMailPoetDataProperty(name: string, value: string) {
  const postId = select(editorStore).getCurrentPostId();
  const editedPost = select(coreDataStore).getEditedEntityRecord(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    postId,
  );

  // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
  const mailpoetData = editedPost?.mailpoet_data || {};
  void dispatch(coreDataStore).editEntityRecord(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    postId,
    {
      mailpoet_data: {
        ...mailpoetData,
        [name]: value,
      },
    },
  );
}

const getVerifiedDomains = () => window.mailpoet_verified_sender_domains ?? [];
const getPartiallyVerifiedDomains = () =>
  window.mailpoet_partially_verified_sender_domains ?? [];
const getAuthorizedEmails = () => window.mailpoet_authorized_emails ?? [];

function isSenderDomainVerified(emailAddress: string): boolean {
  return getVerifiedDomains().includes(extractEmailDomain(emailAddress));
}

function isSenderDomainPartiallyVerified(emailAddress: string): boolean {
  return getPartiallyVerifiedDomains().includes(
    extractEmailDomain(emailAddress),
  );
}

function isSenderEmailAuthorized(emailAddress: string): boolean {
  return getAuthorizedEmails().includes(emailAddress);
}

async function loadAuthorizedEmailAddresses(): Promise<string[]> {
  if (window.mailpoet_mta_method !== 'MailPoet') {
    return [];
  }

  const response = await MailPoet.Ajax.post<AjaxStringListResponse>({
    api_version: window.mailpoet_api_version,
    endpoint: 'mailer',
    action: 'getAuthorizedEmailAddresses',
  });
  const authorizedEmails = response.data || [];
  window.mailpoet_authorized_emails = authorizedEmails;
  return authorizedEmails;
}

async function loadVerifiedSenderDomains(): Promise<string[]> {
  if (window.mailpoet_mta_method !== 'MailPoet') {
    return [];
  }

  const response = await MailPoet.Ajax.post<AjaxStringListResponse>({
    api_version: window.mailpoet_api_version,
    endpoint: 'mailer',
    action: 'getVerifiedSenderDomains',
  });
  const verifiedDomains = response.data || [];
  window.mailpoet_verified_sender_domains = verifiedDomains;
  return verifiedDomains;
}

export function useSenderFields(): UseSenderFields {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    'mailpoet_data',
  );
  const emailData = (mailpoetEmailData || {}) as MailPoetEmailData;

  const senderName = emailData.sender_name || '';
  const senderAddress = emailData.sender_address || '';
  const replyToName = emailData.reply_to_name || '';
  const replyToAddress = emailData.reply_to_address || '';

  const [fieldsWereValidated, setFieldsWereValidated] = useState(false);
  const [isSenderAddressAuthorized, setIsSenderAddressAuthorized] =
    useState(true);
  const [showSenderDomainWarning, setShowSenderDomainWarning] = useState(false);
  const [isPartiallyVerifiedDomain, setIsPartiallyVerifiedDomain] =
    useState(false);
  const [senderAddressWasValidated, setSenderAddressWasValidated] =
    useState(false);
  const [replyToAddressWasValidated, setReplyToAddressWasValidated] =
    useState(false);
  const initialSenderAddressWasValidated = useRef(false);

  const shouldValidateSenderAddress =
    fieldsWereValidated || senderAddressWasValidated;
  const shouldValidateReplyToAddress =
    fieldsWereValidated || replyToAddressWasValidated;

  const senderNameError =
    fieldsWereValidated && !senderName.trim()
      ? __('Please specify a sender name.', 'mailpoet')
      : '';
  let senderAddressError = '';
  if (shouldValidateSenderAddress && !senderAddress.trim()) {
    senderAddressError = __(
      'Please specify a sender email address.',
      'mailpoet',
    );
  } else if (shouldValidateSenderAddress && !isEmail(senderAddress)) {
    senderAddressError = __('Invalid email address', 'mailpoet');
  }
  const replyToAddressError =
    shouldValidateReplyToAddress && replyToAddress && !isEmail(replyToAddress)
      ? __('Invalid email address', 'mailpoet')
      : '';

  const isCampaign = useCallback(() => {
    const campaignTypes = window.mailpoet_sender_restrictions?.campaignTypes;
    if (!campaignTypes?.length) {
      return true;
    }
    return campaignTypes.includes(emailData.type || '');
  }, [emailData.type]);

  const isAuthorizedDomainRequired = useCallback(
    () =>
      Boolean(
        window.mailpoet_sender_restrictions
          ?.isAuthorizedDomainRequiredForNewCampaigns && isCampaign(),
      ),
    [isCampaign],
  );

  const hasSenderAuthorizationError =
    window.mailpoet_mss_active &&
    window.mailpoet_mta_method === 'MailPoet' &&
    !window.mailpoet_sender_restrictions?.skipAuthorization &&
    isEmail(senderAddress) &&
    !isSenderDomainVerified(senderAddress) &&
    fieldsWereValidated &&
    (!isSenderAddressAuthorized || isAuthorizedDomainRequired());

  const hasValidationErrors =
    Boolean(senderNameError) ||
    Boolean(senderAddressError) ||
    Boolean(replyToAddressError) ||
    hasSenderAuthorizationError;

  const setSenderName = (value: string) => {
    setFieldsWereValidated(false);
    updateMailPoetDataProperty('sender_name', value);
  };

  const setSenderAddress = (value: string) => {
    setFieldsWereValidated(false);
    setSenderAddressWasValidated(false);
    setIsSenderAddressAuthorized(true);
    setShowSenderDomainWarning(false);
    setIsPartiallyVerifiedDomain(false);
    updateMailPoetDataProperty('sender_address', value.toLowerCase());
  };

  const setReplyToName = (value: string) => {
    setFieldsWereValidated(false);
    updateMailPoetDataProperty('reply_to_name', value);
  };

  const setReplyToAddress = (value: string) => {
    setFieldsWereValidated(false);
    setReplyToAddressWasValidated(false);
    updateMailPoetDataProperty('reply_to_address', value.toLowerCase());
  };

  const performSenderAddressValidation = useCallback(() => {
    if (
      !window.mailpoet_mss_active ||
      window.mailpoet_mta_method !== 'MailPoet' ||
      window.mailpoet_sender_restrictions?.skipAuthorization ||
      !isEmail(senderAddress)
    ) {
      return;
    }

    if (isSenderDomainVerified(senderAddress)) {
      setIsSenderAddressAuthorized(true);
      setShowSenderDomainWarning(false);
      setIsPartiallyVerifiedDomain(false);
      return;
    }

    setIsSenderAddressAuthorized(isSenderEmailAuthorized(senderAddress));
    setShowSenderDomainWarning(true);
    setIsPartiallyVerifiedDomain(
      isSenderDomainPartiallyVerified(senderAddress),
    );
  }, [senderAddress]);

  const validateSenderFields = useCallback(async () => {
    setFieldsWereValidated(true);
    setSenderAddressWasValidated(true);
    setReplyToAddressWasValidated(true);

    if (
      !senderName.trim() ||
      !senderAddress.trim() ||
      !isEmail(senderAddress) ||
      (replyToAddress && !isEmail(replyToAddress))
    ) {
      return false;
    }

    if (
      !window.mailpoet_mss_active ||
      window.mailpoet_mta_method !== 'MailPoet' ||
      window.mailpoet_sender_restrictions?.skipAuthorization
    ) {
      return true;
    }

    const verifiedDomains = await loadVerifiedSenderDomains();
    const senderDomain = extractEmailDomain(senderAddress);
    if (verifiedDomains.includes(senderDomain)) {
      setIsSenderAddressAuthorized(true);
      setShowSenderDomainWarning(false);
      setIsPartiallyVerifiedDomain(false);
      return true;
    }

    const authorizedEmails = await loadAuthorizedEmailAddresses();
    const isAuthorized = authorizedEmails.includes(senderAddress);
    setIsSenderAddressAuthorized(isAuthorized);
    setShowSenderDomainWarning(true);
    setIsPartiallyVerifiedDomain(
      isSenderDomainPartiallyVerified(senderAddress),
    );
    return isAuthorized && !isAuthorizedDomainRequired();
  }, [isAuthorizedDomainRequired, replyToAddress, senderAddress, senderName]);

  const handleSuccessfulAuthorization = ({
    type,
    data,
  }: AuthorizationSuccessData) => {
    if (type === 'email') {
      window.mailpoet_authorized_emails = [
        ...getAuthorizedEmails().filter((email) => email !== data),
        data,
      ];
      setIsSenderAddressAuthorized(true);
      MailPoet.trackEvent('MSS in plugin authorize email', {
        'authorized email source': 'email editor',
        wasSuccessful: 'yes',
      });
    }
    if (type === 'domain') {
      window.mailpoet_verified_sender_domains = [
        ...getVerifiedDomains().filter((domain) => domain !== data),
        data,
      ];
      window.mailpoet_partially_verified_sender_domains =
        getPartiallyVerifiedDomains().filter((domain) => domain !== data);
      setShowSenderDomainWarning(false);
      setIsPartiallyVerifiedDomain(false);
      MailPoet.trackEvent('MSS in plugin verify sender domain', {
        'verify sender domain source': 'email editor',
        wasSuccessful: 'yes',
      });
    }
  };

  const validateSenderAddress = useCallback(() => {
    setSenderAddressWasValidated(true);
    performSenderAddressValidation();
  }, [performSenderAddressValidation]);

  const validateReplyToAddress = useCallback(() => {
    setReplyToAddressWasValidated(true);
  }, []);

  useEffect(() => {
    if (initialSenderAddressWasValidated.current || !senderAddress) {
      return;
    }

    initialSenderAddressWasValidated.current = true;
    performSenderAddressValidation();
  }, [performSenderAddressValidation, senderAddress]);

  return {
    senderName,
    senderAddress,
    replyToName,
    replyToAddress,
    senderNameError,
    senderAddressError,
    replyToAddressError,
    hasValidationErrors,
    isSenderAddressAuthorized,
    showSenderDomainWarning,
    isPartiallyVerifiedDomain,
    setSenderName,
    setSenderAddress,
    setReplyToName,
    setReplyToAddress,
    validateSenderFields,
    validateSenderAddress,
    validateReplyToAddress,
    handleSuccessfulAuthorization,
  };
}
