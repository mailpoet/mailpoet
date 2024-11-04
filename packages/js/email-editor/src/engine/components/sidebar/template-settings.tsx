import { Panel } from '@wordpress/components';
import { TemplateInfo } from './template-info';
import { TemplatesPanel } from './templates-panel';

export function TemplateSettings() {
  return (
    <Panel>
      <TemplateInfo />
      <TemplatesPanel />
    </Panel>
  );
}
