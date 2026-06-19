import { select, dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../editor/store/constants';
import type { FormTokenItem } from '../../editor/components';
import type { SenderRestrictionsType } from '../../../common';

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
  transactional_triggers?: string[];
  delay_action_key?: string;
  block_email_editor_enabled?: boolean;
};

export const getContext = (): Context =>
  select(storeName).getContext('mailpoet') as Context;

export const useSelectContext = (): Context =>
  useSelect((s) => s(storeName).getContext('mailpoet'), []);

export const updateSenderDomainsConfig = (
  senderDomainsConfig: SenderDomainsConfig,
) => {
  void dispatch(storeName).alterContext('mailpoet', 'senderDomainsConfig', {
    ...senderDomainsConfig,
  });
};
