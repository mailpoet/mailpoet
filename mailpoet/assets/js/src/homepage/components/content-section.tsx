import { ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  heading: string;
  children: ReactNode;
  headingAfter?: ReactNode;
  className?: string;
};

export function ContentSection({
  heading,
  children,
  headingAfter,
  className = '',
}: Props): JSX.Element {
  return (
    <div
      className={classnames('mailpoet-homepage-section__container', className)}
    >
      <div className="mailpoet-homepage-section__heading">
        <h2>{heading}</h2>
        <div className="mailpoet-homepage-section__heading-after">
          {headingAfter}
        </div>
      </div>
      {children}
    </div>
  );
}
