import ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { plusIcon } from 'common/button/icon/plus';
import { Button, Flex } from '@wordpress/components';
import { Workflow } from './listing/workflow';
import { AutomationListing } from './listing';
import { Onboarding } from './onboarding';
import {
  CreateEmptyWorkflowButton,
  CreateWorkflowFromTemplateButton,
} from './testing';
import { useMutation, useQuery } from './api';
import { WorkflowListingNotices } from './listing/workflow-listing-notices';

function Workflows(): JSX.Element {
  const { data, loading, error } = useQuery<{ data: Workflow[] }>('workflows');
  let content;

  if (error) {
    content = <div>Error: {error}</div>;
  } else if (loading) {
    content = <div>Loading workflows...</div>;
  } else {
    const workflows = data?.data ?? [];
    content =
      workflows.length < 1 ? (
        <Onboarding />
      ) : (
        <AutomationListing workflows={workflows} loading={loading} />
      );
  }

  return (
    <>
      <TopBarWithBeamer />
      <Flex className="mailpoet-automation-listing-heading">
        <h1 className="wp-heading-inline">Automations</h1>
        <Button
          onClick={() => {
            // TODO: link to templates page when implemented, MAILPOET-4533
          }}
          icon={plusIcon}
          variant="primary"
        >
          New automation
        </Button>
      </Flex>
      {content}
    </>
  );
}

function RecreateSchemaButton(): JSX.Element {
  const [createSchema, { loading, error }] = useMutation('system/database', {
    method: 'POST',
  });

  return (
    <div>
      <WorkflowListingNotices />
      <button
        className="button button-link-delete"
        type="button"
        onClick={() => createSchema()}
        disabled={loading}
      >
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
        className="button button-link-delete"
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
    <BrowserRouter>
      <div>
        <Workflows />
        <div style={{ marginTop: 30, display: 'grid', gridGap: 8 }}>
          <CreateEmptyWorkflowButton />
          <CreateWorkflowFromTemplateButton slug="simple-welcome-email">
            Create testing workflow from template (welcome email)
          </CreateWorkflowFromTemplateButton>
          <CreateWorkflowFromTemplateButton slug="welcome-email-sequence">
            Create testing workflow from template (welcome sequence, only
            premium)
          </CreateWorkflowFromTemplateButton>
          <CreateWorkflowFromTemplateButton slug="advanced-welcome-email-sequence">
            Create testing workflow from template (advanced welcome sequence,
            only premium)
          </CreateWorkflowFromTemplateButton>
          <RecreateSchemaButton />
          <DeleteSchemaButton />
        </div>
      </div>
    </BrowserRouter>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation');
  if (root) {
    ReactDOM.render(<App />, root);
  }
});
