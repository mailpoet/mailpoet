import { ReactNode } from 'react';
import { Automation } from '../automation';
import type { ManualStartMetadata } from '../manual-start/types';

declare global {
  interface Window {
    mailpoet_roles: Record<string, string>;
    mailpoet_woocommerce_automatic_emails?: Record<
      string,
      {
        slug: string;
        title: string;
        description: string;
        events: Record<string, Record<string, unknown>>;
      }
    >;
    mailpoet_legacy_automations_notice_dismissed: boolean;
  }
}

export type AutomationItem = Automation & {
  description?: ReactNode;
  isLegacy?: boolean;
  manual_start?: ManualStartMetadata;
};

export type State = {
  automations?: AutomationItem[];
  legacyAutomations?: AutomationItem[];
};
