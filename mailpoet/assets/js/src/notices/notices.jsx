import React from 'react';
import { GlobalContext } from 'context/index.jsx';
import Notice from './notice.tsx';

export default () => {
  const { notices } = React.useContext(GlobalContext);
  return notices.items.map(
    ({
      id,
      ...props
    }) => <Notice key={id} {...props} />// eslint-disable-line react/jsx-props-no-spreading
  );
};
