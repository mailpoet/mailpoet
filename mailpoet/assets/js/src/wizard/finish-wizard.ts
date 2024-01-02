import { updateSettings } from './update-settings';

export async function finishWizard(redirect_url = null) {
  await updateSettings({
    version: window.mailpoet_version,
    installed_after_new_domain_restrictions: 1,
  });
  if (redirect_url) {
    window.location.href = redirect_url;
  } else {
    window.location.href = window.finish_wizard_url;
  }
}
