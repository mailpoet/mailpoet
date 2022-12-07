import range from 'lodash/range';
import classnames from 'classnames';
import { withBoundary } from 'common';
import { ContentWrapperFix } from './content_wrapper_fix';

type Props = {
  count: number;
  current: number;
  titles?: string[];
};

function StepsComponent({ count, current, titles }: Props) {
  return (
    <div className="mailpoet-steps">
      <ContentWrapperFix />
      {range(1, count + 1).map((i) => (
        <div
          key={i}
          className={classnames('mailpoet-step', {
            'mailpoet-step-done': i < current,
            'mailpoet-step-active': i === current,
          })}
        >
          <div className="mailpoet-step-badge">{i >= current ? i : ''}</div>
          {titles[i - 1] && (
            <div
              className="mailpoet-step-title"
              data-title={titles[i - 1] || ''}
            >
              {titles[i - 1] || ''}
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

StepsComponent.defaultProps = {
  titles: [],
};
StepsComponent.displayName = 'StepsComponent';
const Steps = withBoundary(StepsComponent);
export { Steps };
