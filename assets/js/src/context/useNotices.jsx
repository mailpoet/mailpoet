import React from 'react';

export default () => {
  const [items, setItems] = React.useState([]);
  const [nextId, setNextId] = React.useState(1);

  const getNextId = () => {
    setNextId((x) => x + 1);
    return nextId;
  };

  const add = (item) => {
    setItems((xs) => [...xs, { ...item, id: item.id || getNextId() }]);
  };

  const remove = (id) => {
    setItems((xs) => xs.filter((x) => x.id !== id));
  };

  const success = (content, props = {}) => add({ ...props, type: 'success', children: content });
  const info = (content, props = {}) => add({ ...props, type: 'info', children: content });
  const warning = (content, props = {}) => add({ ...props, type: 'warning', children: content });
  const error = (content, props = {}) => add({ ...props, type: 'error', children: content });

  return {
    items, success, info, warning, error, remove,
  };
};

