import React from 'react';
import Header from './header.jsx';
import Sidebar from './sidebar.jsx';
import FormTitle from './form_title.jsx';

export default () => (
  <div className="edit-post-layout is-sidebar-opened">
    <Header />
    <div className="edit-post-layout__content">
      <div className="edit-post-visual-editor editor-styles-wrapper">
        <div className="editor-writing-flow block-editor-writing-flow">
          <FormTitle />
        </div>
      </div>
    </div>
    <div>
      <Sidebar />
    </div>
  </div>
);
