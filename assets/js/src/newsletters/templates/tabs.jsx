import React from 'react';
import PropTypes from 'prop-types';
import ReactTooltip from 'react-tooltip';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const tooltipText = ReactStringReplace(
  MailPoet.I18n.t('tooltipTemplateTabs'),
  /\[link\](.*?)\[\/link\]/g,
  match => (
    <a
      href="https://mailpoet.polldaddy.com/s/select-template-feedback"
      key="feedback"
      target="_blank"
      rel="noopener noreferrer"
    >{ match }</a>
  )
);

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
    <span
      className="feedback-tooltip newsletter-templates-feedback"
      data-event="click"
      data-tip
      data-for="feedback-newsletter-templates-tabs"
    >{MailPoet.I18n.t('feedback')}</span>
    <ReactTooltip
      globalEventOff="click"
      multiline
      id="feedback-newsletter-templates-tabs"
      efect="solid"
      place="bottom"
    >
      <span
        style={{
          pointerEvents: 'all',
          display: 'inline-block',
        }}
      >
        {tooltipText}
      </span>
    </ReactTooltip>
  </div>
);

Tabs.propTypes = {
  selected: PropTypes.string.isRequired,
  select: PropTypes.func.isRequired,
  tabs: PropTypes.arrayOf(PropTypes.shape({
    label: PropTypes.string.isRequired,
    name: PropTypes.string.isRequired,
  }).isRequired).isRequired,
};

export default Tabs;
