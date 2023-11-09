export type AutomationTemplate = {
  slug: string;
  name: string;
  description: string;
  category: string;
  type: 'default' | 'free-only' | 'premium' | 'coming-soon';
};

export type AutomationTemplateCategory = {
  slug: string;
  name: string;
};

declare global {
  interface Window {
    mailpoet_automation_templates: AutomationTemplate[];
    mailpoet_automation_template_categories: AutomationTemplateCategory[];
  }
}

export const automationTemplates = window.mailpoet_automation_templates;

export const automationTemplateCategories =
  window.mailpoet_automation_template_categories;
