import React from 'react';
import { useSelect } from '@wordpress/data';
import classnames from 'classnames';
import Header from './header.jsx';
import Sidebar from './sidebar.jsx';
import FormTitle from './form_title.jsx';

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
        <div className="edit-post-visual-editor editor-styles-wrapper">
          <div className="editor-writing-flow block-editor-writing-flow">
            <FormTitle />
          </div>
        </div>
      </div>
      <div>
        { sidebarOpened ? <Sidebar /> : '' }
      </div>
    </div>
  );
};
