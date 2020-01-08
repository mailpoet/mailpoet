import React, { useRef } from 'react';
import { DndProvider as ReactDndProvider, createDndContext } from 'react-dnd';
import Backend from 'react-dnd-html5-backend';
import PropTypes from 'prop-types';

const DndProvider = ({ children }) => {
  const manager = useRef(createDndContext(Backend));
  return (
    <ReactDndProvider manager={manager.current.dragDropManager}>
      {children}
    </ReactDndProvider>
  );
};

DndProvider.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export default DndProvider;
