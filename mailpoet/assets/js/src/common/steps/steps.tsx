import range from 'lodash/range';
import React from 'react';
import classNames from 'classnames';
import { ContentWrapperFix } from './content_wrapper_fix';

type Props = {
  count: number;
  current: number;
  titles?: string[];
};

const Steps = ({ count, current, titles }: Props) => (
  <div className="mailpoet-steps">
    {range(1, count + 1).map((i) => (
      <div
        key={i}
        className={classNames('mailpoet-step', {
          'mailpoet-step-done': i < current,
          'mailpoet-step-active': i === current,
        })}
      >
        <div className="mailpoet-step-badge">{i >= current ? i : ''}</div>
        {titles[i - 1] && <div className="mailpoet-step-title" data-title={titles[i - 1] || ''}>{titles[i - 1] || ''}</div>}
      </div>
    ))}
    <ContentWrapperFix />
  </div>
);

Steps.defaultProps = {
  titles: [],
};

export default Steps;
