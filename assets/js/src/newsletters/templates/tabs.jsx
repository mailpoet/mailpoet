import React from 'react';

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

export default Tabs;
