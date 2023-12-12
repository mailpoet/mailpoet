import { ReactNode } from 'react';
import { Automation } from '../automation';

declare global {
  interface Window {
    mailpoet_segments: {
      id: string;
      name: string;
      subscribers: string;
      type: 'default' | 'wp_users' | 'woocommerce_users' | 'dynamic';
    }[];
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
  }
}

export type AutomationItem = Automation & {
  description?: ReactNode;
  isLegacy?: boolean;
};

export type State = {
  automations?: AutomationItem[];
  legacyAutomations?: AutomationItem[];
};
