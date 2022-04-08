import { useState, useCallback } from 'react';

export default () => {
  const [state, setState] = useState({
    items: [],
    nextId: 1,
  });

  const add = useCallback(
    (item) => {
      setState(({ items, nextId }) => ({
        items: [...items, { ...item, id: item.id || nextId }],
        nextId: item.id ? nextId : nextId + 1,
      }));
    },
    [setState],
  );

  const remove = useCallback(
    (id) => {
      setState(({ items, nextId }) => ({
        items: items.filter((x) => x.id !== id),
        nextId,
      }));
    },
    [setState],
  );

  const success = useCallback(
    (content, props = {}) =>
      add({ ...props, type: 'success', children: content }),
    [add],
  );
  const info = useCallback(
    (content, props = {}) => add({ ...props, type: 'info', children: content }),
    [add],
  );
  const warning = useCallback(
    (content, props = {}) =>
      add({ ...props, type: 'warning', children: content }),
    [add],
  );
  const error = useCallback(
    (content, props = {}) =>
      add({ ...props, type: 'error', children: content }),
    [add],
  );

  return {
    items: state.items,
    success,
    info,
    warning,
    error,
    remove,
  };
};
