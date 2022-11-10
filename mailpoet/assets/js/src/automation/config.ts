declare global {
  interface Window {
    mailpoet_automation_api: {
      root: string;
      nonce: string;
    };
    mailpoet_automation_count: number;
  }
}

export const api = window.mailpoet_automation_api;
export const automationCount = window.mailpoet_automation_count;
