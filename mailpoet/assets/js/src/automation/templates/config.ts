export type WorkflowTemplate = {
  slug: string;
  name: string;
  description: string;
  type: 'default' | 'free-only' | 'premium' | 'coming-soon';
};

declare global {
  interface Window {
    mailpoet_automation_templates: WorkflowTemplate[];
  }
}

export const workflowTemplates = window.mailpoet_automation_templates;
