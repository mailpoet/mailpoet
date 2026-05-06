import { registerStepType } from '../../editor/store';
import { step as MadeACommentTrigger } from './steps/made-a-comment';
import { step as ChangeUserRole } from './steps/change-user-role';
// Insert new imports here

export const initialize = (): void => {
  registerStepType(MadeACommentTrigger);
  registerStepType(ChangeUserRole);
  // Insert new steps here
};
