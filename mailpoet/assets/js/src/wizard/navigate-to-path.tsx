import { updateSettings } from './update-settings';

export const navigateToPath = (
  navigate,
  path: string,
  replaceCurrent = false,
) => {
  void updateSettings({ welcome_wizard_current_step: path });
  if (replaceCurrent) {
    navigate(path, { replace: true });
  } else {
    navigate(path);
  }
};
