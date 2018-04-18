import React from 'react';
import PropTypes from 'prop-types';

const Tabs = ({ tabs, selected, select }) => (
  <div className="wp-filter hide-if-no-js">
    <ul className="filter-links">
      {tabs.map(({ name, label }) => (
        <li key={name}><a
          href="javascript:"
          className={selected === name ? 'current' : ''}
          onClick={() => select(name)}
        > {label}
        </a></li>
      ))}
    </ul>
  </div>
);

Tabs.propTypes = {
  selected: PropTypes.string.isRequired,
  select: PropTypes.func.isRequired,
  tabs: PropTypes.arrayOf(PropTypes.shape({
    label: PropTypes.func.isRequired,
    name: PropTypes.func.isRequired,
  }).isRequired).isRequired,
};

export default Tabs;
