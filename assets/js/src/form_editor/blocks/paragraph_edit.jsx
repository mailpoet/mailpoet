import React from 'react';
import PropTypes from 'prop-types';

const ParagraphEdit = ({ children }) => (
  <div className="mailpoet_paragraph">
    {children}
  </div>
);

ParagraphEdit.propTypes = {
  children: PropTypes.node.isRequired,
};

export default ParagraphEdit;
