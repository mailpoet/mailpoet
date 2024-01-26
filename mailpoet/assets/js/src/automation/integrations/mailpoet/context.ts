import { select } from '@wordpress/data';
import { FormTokenItem } from '../../editor/components';
import { storeName } from '../../editor/store';
import { SenderRestrictionsType } from '../../../common';

type Segment = FormTokenItem & {
  type: string;
};

type SenderDomainsConfig = {
  authorizedEmails: string[];
  verifiedSenderDomains: string[];
  partiallyVerifiedSenderDomains: string[];
  allSenderDomains: string[];
  senderRestrictions: SenderRestrictionsType;
};

export type Context = {
  segments?: Segment[];
  userRoles?: FormTokenItem[];
  senderDomainsConfig?: SenderDomainsConfig;
};

export const getContext = (): Context =>
  select(storeName).getContext('mailpoet') as Context;
