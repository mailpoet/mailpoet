type SenderDomainVerificationStatus = 'valid' | 'invalid' | 'pending';

type SenderDomainDnsItem = {
  host: string;
  value: string;
  type: string;
  status: SenderDomainVerificationStatus;
  message: string;
};

type SenderDomainEntity = {
  domain: string;
  dns: Array<SenderDomainDnsItem>;
};

export {
  SenderDomainVerificationStatus,
  SenderDomainDnsItem,
  SenderDomainEntity,
};
