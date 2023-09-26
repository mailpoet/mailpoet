import { DetailsPanel } from './details-panel';
import { EmailTypeInfo } from './email-type-info';

/**
 * Component for rendering the sidebar in the email editor
 */
export function SettingsSidebar() {
  return (
    <>
      <EmailTypeInfo />
      <DetailsPanel />
    </>
  );
}
