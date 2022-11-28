import { useContext } from 'react';
import { GlobalContext } from 'context/index.jsx';
import { Notice } from './notice.tsx';

const Notices = () => {
  const { notices } = useContext(GlobalContext);
  return notices.items.map(({ id, ...props }) => (
    <Notice key={id} {...props} />
  ));
};

Notices.displayName = 'Notices';

export { Notices };
