import { History } from 'history';
import { updateSettings } from './update-settings';

export const navigateToPath = (
  history: History,
  path: string,
  replaceCurrent = false,
) => {
  void updateSettings({ welcome_wizard_current_step: path });
  if (replaceCurrent) {
    history.replace(path);
  } else {
    history.push(path);
  }
};
