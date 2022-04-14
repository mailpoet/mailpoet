import { useContext } from 'react';
import { GlobalContext } from 'context/index.jsx';
import { Notice } from './notice.tsx';

export const Notices = () => {
  const { notices } = useContext(GlobalContext);
  return notices.items.map(({ id, ...props }) => (
    <Notice key={id} {...props} />
  ));
};
