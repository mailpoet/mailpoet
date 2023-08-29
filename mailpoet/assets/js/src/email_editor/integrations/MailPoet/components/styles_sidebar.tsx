import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
import { PluginSidebar } from '@wordpress/edit-post';
import { styles } from '@wordpress/icons';

export function StylesSidebar() {
  return (
    <PluginSidebar name="my-sidebar" title="Styles sidebar" icon={styles}>
      <PanelBody>{__('Style sidebar content')}</PanelBody>
    </PluginSidebar>
  );
}
