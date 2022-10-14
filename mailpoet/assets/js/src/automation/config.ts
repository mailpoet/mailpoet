declare global {
  interface Window {
    mailpoet_automation_api: {
      root: string;
      nonce: string;
    };
    mailpoet_workflow_count: number;
  }
}

export const api = window.mailpoet_automation_api;
export const workflowCount = window.mailpoet_workflow_count;
