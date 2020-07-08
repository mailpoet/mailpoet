import React from 'react';
import { useDispatch } from '@wordpress/data';
import DefaultSidebar from './default_sidebar';

export default () => {
  const { toggleSidebar } = useDispatch('mailpoet-form-editor');
  return (
    <div className="edit-post-sidebar interface-complementary-area mailpoet_form_editor_sidebar">
      <DefaultSidebar onClose={() => toggleSidebar(false)} />
    </div>
  );
};
