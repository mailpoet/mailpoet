import { updateSettings } from './updateSettings';

export async function finishWizard(redirect_url = null) {
  await updateSettings({
    version: window.mailpoet_version,
  });
  if (redirect_url) {
    window.location.href = redirect_url;
  } else {
    window.location.href = window.finish_wizard_url;
  }
}
