import { useContext } from 'react';
import { GlobalContext } from 'context/index.jsx';
import { withBoundary } from 'common';
import { Notice } from './notice.tsx';

const NoticesComponent = () => {
  const { notices } = useContext(GlobalContext);
  return notices.items.map(({ id, ...props }) => (
    <Notice key={id} {...props} />
  ));
};

NoticesComponent.displayName = 'Notices';
const Notices = withBoundary(NoticesComponent);
export { Notices };
