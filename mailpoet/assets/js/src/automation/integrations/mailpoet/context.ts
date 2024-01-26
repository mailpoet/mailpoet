import { select } from '@wordpress/data';
import { FormTokenItem } from '../../editor/components';
import { storeName } from '../../editor/store';

type Segment = FormTokenItem & {
  type: string;
};

type SenderRestrictions = {
  lowerLimit: number;
  upperLimit: number;
};

type SenderDomainsConfig = {
  authorizedEmails: string[];
  verifiedSenderDomains: string[];
  partiallyVerifiedSenderDomains: string[];
  allSenderDomains: string[];
  senderRestrictions: SenderRestrictions;
};

export type Context = {
  segments?: Segment[];
  userRoles?: FormTokenItem[];
  senderDomainsConfig?: SenderDomainsConfig;
};

export const getContext = (): Context =>
  select(storeName).getContext('mailpoet') as Context;
