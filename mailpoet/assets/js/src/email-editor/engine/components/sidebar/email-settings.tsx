import { Panel } from '@wordpress/components';
import { DetailsPanel } from './details-panel';
import { EmailTypeInfo } from './email-type-info';
import { TemplatesPanel } from './templates-panel';

export function EmailSettings() {
  return (
    <Panel>
      <EmailTypeInfo />
      <TemplatesPanel />
      <DetailsPanel />
    </Panel>
  );
}
