import React from 'react';
import PropTypes from 'prop-types';
import Notice from 'notices/notice.jsx';

const APIErrorsNotice = ({ errors }) => {
  if (errors.length < 1) return null;
  return <Notice type="error" closable={false}>{errors.map((err) => <p key={err.message}>{err.message}</p>)}</Notice>;
};
APIErrorsNotice.propTypes = {
  errors: PropTypes.arrayOf(PropTypes.shape({
    message: PropTypes.string.isRequired,
  })).isRequired,
};

export default APIErrorsNotice;
