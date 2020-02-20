import React from 'react';

export default () => {
  const [items, setItems] = React.useState([]);
  const [nextId, setNextId] = React.useState(1);

  const getNextId = React.useCallback(() => {
    setNextId((x) => x + 1);
    return nextId;
  }, [nextId]);

  const add = React.useCallback((item) => {
    setItems((xs) => [...xs, { ...item, id: item.id || getNextId() }]);
  }, [getNextId]);

  const remove = React.useCallback((id) => {
    setItems((xs) => xs.filter((x) => x.id !== id));
  }, []);

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
    items, success, info, warning, error, remove,
  };
};
