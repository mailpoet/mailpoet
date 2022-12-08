import { FormTokenItem } from '../../../../../editor/components';

declare global {
  interface Window {
    mailpoet_user_roles: Record<string, string>;
  }
}

export const userRoles: FormTokenItem[] = Object.keys(
  window.mailpoet_user_roles,
).map((id: string): FormTokenItem => {
  const role = {
    id,
    name: window.mailpoet_user_roles[id],
  };
  return role;
});
