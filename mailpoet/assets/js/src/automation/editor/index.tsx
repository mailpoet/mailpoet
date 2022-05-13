import classnames from 'classnames';
import ReactDOM from 'react-dom';
import { useSelect } from '@wordpress/data';
import { InterfaceSkeleton, FullscreenMode } from '@wordpress/interface';
import { store } from './store';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/layout/index.js

function Editor(): JSX.Element {
  const { isFullscreenActive } = useSelect(
    (select) => ({
      isFullscreenActive: select(store).isFeatureActive('fullscreenMode'),
    }),
    [],
  );

  const className = classnames(
    'edit-post-layout',
    'interface-interface-skeleton',
  );

  return (
    <>
      <FullscreenMode isActive={isFullscreenActive} />
      <InterfaceSkeleton
        className={className}
        header={<div>Header</div>}
        content={<div>Content</div>}
        sidebar={<div>Sidebar</div>}
        secondarySidebar={<div>Secondary sidebar</div>}
      />
    </>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    ReactDOM.render(<Editor />, root);
  }
});
