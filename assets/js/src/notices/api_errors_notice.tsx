import React, { FC } from 'react';
import PropTypes, { InferProps } from 'prop-types';
import Notice from 'notices/notice.tsx';

const propTypes = {
  errors: PropTypes.arrayOf(PropTypes.shape({
    message: PropTypes.string.isRequired,
  })).isRequired,
};

const APIErrorsNotice: FC<InferProps<typeof propTypes>> = ({ errors }) => {
  if (errors.length < 1) return null;
  return <Notice type="error" closable={false}>{errors.map((err) => <p key={err.message}>{err.message}</p>)}</Notice>;
};

APIErrorsNotice.propTypes = propTypes;

export default APIErrorsNotice;
