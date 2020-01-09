import React from 'react';
import PropTypes from 'prop-types';

const AddCustomField = ({ clientId }) => {
  return (
    <>
      <p id={clientId}>Create a new custom field for your subscribers.</p>
    </>
  );
};

AddCustomField.propTypes = {
  clientId: PropTypes.string.isRequired,
};

export default AddCustomField;
