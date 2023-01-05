import { ErrorBoundary } from 'common';
import { TaskList } from 'homepage/components/task-list';
import { ProductDiscovery } from 'homepage/components/product-discovery';

export function HomepageSections(): JSX.Element {
  return (
    <div className="mailpoet-homepage__container">
      <ErrorBoundary>
        <TaskList />
      </ErrorBoundary>
      <ErrorBoundary>
        <ProductDiscovery />
      </ErrorBoundary>
    </div>
  );
}
