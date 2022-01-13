import React from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';

const ParagraphEdit = ({ children, className }) => (
  <div className={classnames('mailpoet_paragraph', className)}>
    {children}
  </div>
);

ParagraphEdit.propTypes = {
  children: PropTypes.node.isRequired,
  className: PropTypes.string,
};

ParagraphEdit.defaultProps = {
  className: '',
};

export default ParagraphEdit;
