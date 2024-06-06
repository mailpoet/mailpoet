type SenderDomainVerificationStatus = 'valid' | 'invalid' | 'pending';

type SenderDomainDnsItem = {
  host: string;
  current_host?: string;
  value: string;
  current_value?: string;
  type: string;
  status: SenderDomainVerificationStatus;
  message: string;
  issues?: {
    has_errors: boolean;
    has_multiple_values?: boolean;
    needs_additions?: boolean;
    needs_deletions?: boolean;
    duplicate_values?: Array<string>;
    diff?: string;
  };
  host_issues?: {
    has_errors: boolean;
    needs_additions?: boolean;
    needs_deletions?: boolean;
    diff?: string;
  };
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
