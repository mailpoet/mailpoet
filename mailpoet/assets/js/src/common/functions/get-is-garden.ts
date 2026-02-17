declare global {
  interface Window {
    mailpoet_automation_context?: { is_garden?: boolean };
  }
}

export const getIsGarden = (): boolean =>
  window.mailpoet_automation_context?.is_garden === true;
