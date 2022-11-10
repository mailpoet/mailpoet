export type AutomationTemplate = {
  slug: string;
  name: string;
  description: string;
  type: 'default' | 'free-only' | 'premium' | 'coming-soon';
};

declare global {
  interface Window {
    mailpoet_automation_templates: AutomationTemplate[];
  }
}

export const automationTemplates = window.mailpoet_automation_templates;
