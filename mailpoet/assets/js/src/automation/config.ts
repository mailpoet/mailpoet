declare global {
  interface Window {
    mailpoet_automation_api: {
      root: string;
      nonce: string;
    };
  }
}

export const api = window.mailpoet_automation_api;
