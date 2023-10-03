import { registerStepType } from '../../editor/store';
import { step as MadeACommentTrigger } from './steps/made-a-comment';
// Insert new imports here

export const initialize = (): void => {
  registerStepType(MadeACommentTrigger);
  // Insert new steps here
};
