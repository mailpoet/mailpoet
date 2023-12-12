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
