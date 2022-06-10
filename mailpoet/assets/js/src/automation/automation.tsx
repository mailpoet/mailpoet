import ReactDOM from 'react-dom';
import { Search, TableCard } from '@woocommerce/components';
import {
  CreateTestingWorkflowButton,
  CreateWorkflowFromTemplateButton,
} from './testing';
import { useMutation, useQuery } from './api';

function Workflows(): JSX.Element {
  const { data, loading, error } = useQuery('workflows');

  if (error) {
    return <div>Error: {error}</div>;
  }

  const workflows = data?.data ?? [];

  const rows = workflows.map((workflow) => [
    {
      value: workflow.name,
      display: (
        <a
          href={`admin.php?page=mailpoet-automation-editor&id=${
            workflow.id as number
          }`}
        >
          {workflow.name}
        </a>
      ),
    },
    {
      value: workflow.status,
      display: workflow.status,
    },
  ]);

  const headers = [
    { key: 'name', label: 'Name' },
    { key: 'status', label: 'Status' },
  ];

  return (
    <TableCard
      title=""
      isLoading={!data || loading}
      rows={rows}
      headers={headers}
      query={{ page: 2 }}
      rowsPerPage={7}
      totalRows={workflows.length}
      hasSearch
      actions={[
        <ul className="subsubsub" style={{ width: '400px' }}>
          <li>
            <a href="/">All</a> |
          </li>
          <li>
            <a href="/">Activated</a> |
          </li>
          <li>
            <a href="/">Drafts</a>
          </li>
        </ul>,
        <Search
          allowFreeTextSearch
          inlineTags
          key="search"
          //onChange={ onSearchChange }
          //placeholder={
          //  labels.placeholder ||
          //  __( 'Search by item name', 'woocommerce' )
          //}
          //selected={ searchedLabels }
          showClearButton
          type="custom"
          disabled={!data || loading || data.length === 0}
          autocompleter={{}}
        />,
      ]}
    />
  );
}

function RecreateSchemaButton(): JSX.Element {
  const [createSchema, { loading, error }] = useMutation('system/database', {
    method: 'POST',
  });

  return (
    <div>
      <button type="button" onClick={() => createSchema()} disabled={loading}>
        Recreate DB schema (data will be lost)
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

function DeleteSchemaButton(): JSX.Element {
  const [deleteSchema, { loading, error }] = useMutation('system/database', {
    method: 'DELETE',
  });

  return (
    <div>
      <button
        type="button"
        onClick={async () => {
          await deleteSchema();
          window.location.href =
            '/wp-admin/admin.php?page=mailpoet-experimental';
        }}
        disabled={loading}
      >
        Delete DB schema & deactivate feature
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

function App(): JSX.Element {
  return (
    <div>
      <Workflows />
      <CreateTestingWorkflowButton />
      <CreateWorkflowFromTemplateButton />
      <RecreateSchemaButton />
      <DeleteSchemaButton />
    </div>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation');
  if (root) {
    ReactDOM.render(<App />, root);
  }
});
