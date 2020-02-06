import React from 'react';
import PropTypes from 'prop-types';


function Preview({
  children,
}) {
  return (
    <>
      {children}
    </>
  );
}

Preview.propTypes = {
  children: PropTypes.node.isRequired,
};

export default Preview;
