import { DetailsPanel } from './details_panel';
import { EmailTypeInfo } from './email_type_info';

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
