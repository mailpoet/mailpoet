# CircleCI beta jobs

Step 3 of the `beta-compat-test` skill.

MailPoet’s CircleCI config has job parameters for beta runs (`use_wordpress_beta`, `use_woocommerce_beta` — see `.circleci/config.yml`), but they are not top-level pipeline parameters. Do not trigger a pipeline with `parameters: {"use_woocommerce_beta": true}` unless `.circleci/config.yml` explicitly declares those names under top-level `parameters:`. CircleCI rejects undeclared pipeline parameters, and a normal `build_and_test` pipeline does not run the scheduled beta jobs.

The scheduled `nightly` workflow picks up the latest WooCommerce / WordPress beta automatically. Check the latest scheduled `trunk` pipelines and inspect the beta jobs:

```bash
# Set CIRCLECI_TOKEN to a token with access to the mailpoet/mailpoet project.
TOKEN="$CIRCLECI_TOKEN"

curl -sH "Circle-Token: $TOKEN" \
  "https://circleci.com/api/v2/project/gh/mailpoet/mailpoet/pipeline?branch=trunk" \
  | jq -r '.items[] | select(.trigger.type == "schedule") | [.id,.number,.created_at,.state] | @tsv'
```

For each relevant scheduled pipeline id:

1. Find workflow ids:
   ```bash
   curl -sH "Circle-Token: $TOKEN" \
     "https://circleci.com/api/v2/pipeline/<PIPELINE_ID>/workflow"
   ```
2. Find jobs in the `nightly` workflow:
   ```bash
   curl -sH "Circle-Token: $TOKEN" \
     "https://circleci.com/api/v2/workflow/<WORKFLOW_ID>/job"
   ```
3. Confirm `integration_tests_woocommerce_beta` and `acceptance_tests_woocommerce_beta` passed. If testing WordPress beta too, confirm the corresponding WordPress beta jobs passed.
4. Fetch logs for the beta jobs and verify the expected beta version was installed:
   ```bash
   curl -sH "Circle-Token: $TOKEN" \
     "https://circleci.com/api/v1.1/project/github/mailpoet/mailpoet/<JOB_NUMBER>" \
     | jq '.steps[].actions[] | {name, status, output_url}'
   ```
5. For any failed job, fetch the raw `output_url` values, analyze what went wrong, and classify the failure using [error-triage.md](error-triage.md).

Premium compatibility is covered by the premium jobs in the same `nightly` workflow (`*_with_premium_*`). Check those jobs when WooCommerce-specific premium features such as Subscriptions, Bookings, or Memberships are in scope.
