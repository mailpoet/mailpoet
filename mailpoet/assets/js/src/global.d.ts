/* eslint-disable @typescript-eslint/no-explicit-any */

declare module 'wp-js-hooks' {
  function addFilter(name: string, namespace: string, callback: (...args: any[]) => any): void;
  function applyFilters(name: string, ...args: any[]): any;
}

/* Type definitions for components used from external libraries */
declare module '@woocommerce/blocks-checkout' {
  type CheckboxControlProps = {
    className?: string;
    label?: string;
    id?: string;
    instanceId?: string;
    onChange?: (value: boolean) => void;
    children?: React.ReactChildren|React.ReactElement;
    hasError?: boolean;
    checked?: boolean;
  };
  export const CheckboxControl: (props: CheckboxControlProps) => JSX.Element;
}

declare module '@woocommerce/settings' {
  interface MailPoetSettings {
    optinEnabled: boolean;
    defaultText: string;
    defaultStatus: boolean;
  }
  function getSetting(name: 'mailpoet_data'): MailPoetSettings;
  function getSetting(name: 'adminUrl'): string;
}

declare module '@woocommerce/blocks-checkout' {
  import type { BlockConfiguration } from '@wordpress/blocks';

  interface CheckoutBlockOptionsMetadata extends Partial< BlockConfiguration > {
    name: string;
    parent: string[];
  }

  type CheckoutBlockOptions = {
    metadata: CheckoutBlockOptionsMetadata;
    component: (x) => JSX.Element;
  };

  function registerCheckoutBlock(options: CheckoutBlockOptions): void;
}

interface Window {
  mailpoet_date_offset?: string;
  mailpoet_datetime_format?: string;
  mailpoet_date_format?: string;
  mailpoet_time_format?: string;
}
