import { registerStepType } from '../../editor/store';
import { step as SendEmailStep } from './steps/send-email';
import { step as SomeoneSubscribesTrigger } from './steps/someone-subscribes';
import { step as WpUserRegisteredTrigger } from './steps/wp-user-registered';
import { step as AddTagsAction } from './steps/add-tags';
import { step as RemoveTagsAction } from './steps/remove-tags';
import { step as AddToListStep } from './steps/add-to-list';
import { step as RemoveFromListStep } from './steps/remove-from-list';
import { step as UpdateSubscriberStep } from './steps/update-subscriber';
import { step as UnsubscribeStep } from './steps/unsubscribe';
import { step as NotificationEmail } from './steps/notification-email';
import { step as CustomTriggerStep } from './steps/custom-trigger';
import { step as CustomActionStep } from './steps/custom-action';
import { registerStepControls } from './step-controls';
import { registerAutomationSidebar } from './automation-sidebar';
import { step as TagAddedTrigger } from './steps/tag-added';
import { step as TagRemovedTrigger } from './steps/tag-removed';
import { step as ClicksEmailLinkTrigger } from './steps/clicks-email-link';
// Insert new imports here

export const initialize = (): void => {
  registerStepType(SendEmailStep);
  registerStepType(WpUserRegisteredTrigger);
  registerStepType(SomeoneSubscribesTrigger);
  registerStepType(CustomTriggerStep);
  registerStepType(CustomActionStep);
  registerStepType(AddTagsAction);
  registerStepType(RemoveTagsAction);
  registerStepType(AddToListStep);
  registerStepType(RemoveFromListStep);
  registerStepType(UpdateSubscriberStep);
  registerStepType(UnsubscribeStep);
  registerStepType(NotificationEmail);
  registerStepType(TagAddedTrigger);
  registerStepType(TagRemovedTrigger);
  registerStepType(ClicksEmailLinkTrigger);
  // Insert new steps here
  registerStepControls();
  registerAutomationSidebar();
};
