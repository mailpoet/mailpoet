import React, { FC } from 'react';
import Notice from 'notices/notice';

type Props = {
  errors: Array<{ message: string }>
}

const APIErrorsNotice: FC<Props> = ({ errors }) => {
  if (errors.length < 1) return null;
  return <Notice type="error" closable={false}>{errors.map((err) => <p key={err.message}>{err.message}</p>)}</Notice>;
};

export default APIErrorsNotice;
