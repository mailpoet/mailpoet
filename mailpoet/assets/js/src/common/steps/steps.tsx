import range from 'lodash/range';
import classnames from 'classnames';
import { withBoundary } from 'common';
import { ContentWrapperFix } from './content-wrapper-fix';

type Props = {
  count: number;
  current: number;
  titles?: string[];
  doneCallback?: (step: string) => void;
};

function StepsComponent({ count, current, doneCallback, titles = [] }: Props) {
  return (
    <div className="mailpoet-steps">
      <ContentWrapperFix />
      {range(1, count + 1).map((i) => {
        const isDone = i < current;
        const BadgeComponent = isDone && doneCallback ? 'button' : 'div';

        return (
          <div
            key={i}
            className={classnames('mailpoet-step', {
              'mailpoet-step-done': isDone,
              'mailpoet-step-active': i === current,
            })}
          >
            <BadgeComponent
              className={classnames('mailpoet-step-badge', {
                'mailpoet-step-badge-has-callback': isDone && doneCallback,
              })}
              onClick={() => {
                if (isDone && doneCallback) {
                  doneCallback(i.toString());
                }
              }}
              {...(isDone && doneCallback ? { type: 'button' } : {})}
            >
              {i >= current ? i : ''}
            </BadgeComponent>
            {titles[i - 1] && (
              <div
                className="mailpoet-step-title"
                data-title={titles[i - 1] || ''}
              >
                {titles[i - 1] || ''}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

StepsComponent.displayName = 'StepsComponent';
const Steps = withBoundary(StepsComponent);
export { Steps };
