#!/bin/bash

# Rewrites the file attribute of every test case in a JUnit report to file:method, the
# form list_tests.sh uses for Cest tests, so CircleCI’s stored timings match the split.
# Usage: junit_file_per_test.sh <report.xml>

set -eo pipefail

report=$1
if [ ! -f "$report" ]; then
  echo "No report at $report"
  exit 0
fi

# Codeception writes the attributes in this order: name, class, file
sed -E -i.bak 's#(<testcase name="([^"]+)" class="[^"]*" file="[^"]+)"#\1:\2"#' "$report"
rm -f "$report.bak"
