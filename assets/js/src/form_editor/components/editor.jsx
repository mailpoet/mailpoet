import React from 'react';
import { useSelect } from '@wordpress/data';
import classnames from 'classnames';
import Header from './header.jsx';
import Sidebar from './sidebar.jsx';
import FormTitle from './form_title.jsx';
import Notices from './notices.jsx';

/**
 * This component renders the form editor app.
 * Class names and organization of elements are done based on Gutenberg's edit-post package.
 * (https://github.com/WordPress/gutenberg/tree/master/packages/edit-post).
 * The goal is to render the same DOM for layout as Gutenberg post/page editor
 * so that we can reuse it's CSS.
 */
export default () => {
  const sidebarOpened = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpened(),
    []
  );
  const layoutClass = classnames('edit-post-layout', {
    'is-sidebar-opened': sidebarOpened,
  });
  return (
    <div className={layoutClass}>
      <Header />
      <div className="edit-post-layout__content">
        <Notices />
        <div className="edit-post-visual-editor editor-styles-wrapper">
          <div className="editor-writing-flow block-editor-writing-flow">
            <FormTitle />
          </div>
        </div>
      </div>
      <div>
        { sidebarOpened ? <Sidebar /> : null }
      </div>
    </div>
  );
};
