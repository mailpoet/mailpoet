import React from 'react';
import classnames from 'classnames';
import {
  HorizontalRule,
} from '@wordpress/components';

const DividerEdit = (attributes) => (
  <HorizontalRule className={classnames('mailpoet_divider', attributes.className)} />
);
export default DividerEdit;
