import React from 'react';
import {
  HorizontalRule,
} from '@wordpress/components';

import ParagraphEdit from '../paragraph_edit.jsx';

const DividerEdit = () => (
  <ParagraphEdit>
    <HorizontalRule className="mailpoet_divider" />
  </ParagraphEdit>
);
export default DividerEdit;
