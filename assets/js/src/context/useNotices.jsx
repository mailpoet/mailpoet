import React from 'react';

export default () => {
  const [state, setState] = React.useState({
    items: [],
    nextId: 1,
  });

  const add = React.useCallback((item) => {
    setState(({ items, nextId }) => ({
      items: [...items, { ...item, id: item.id || nextId }],
      nextId: item.id ? nextId : nextId + 1,
    }));
  }, [setState]);

  const remove = React.useCallback((id) => {
    setState(({ items, nextId }) => ({
      items: items.filter((x) => x.id !== id),
      nextId,
    }));
  }, [setState]);

  const success = React.useCallback(
    (content, props = {}) => add({ ...props, type: 'success', children: content }),
    [add]
  );
  const info = React.useCallback(
    (content, props = {}) => add({ ...props, type: 'info', children: content }),
    [add]
  );
  const warning = React.useCallback(
    (content, props = {}) => add({ ...props, type: 'warning', children: content }),
    [add]
  );
  const error = React.useCallback(
    (content, props = {}) => add({ ...props, type: 'error', children: content }),
    [add]
  );

  return {
    items: state.items, success, info, warning, error, remove,
  };
};
