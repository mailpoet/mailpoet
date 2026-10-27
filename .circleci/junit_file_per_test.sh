#!/bin/bash

# Rewrites the file attribute of every test case in a JUnit report to file:method, the
# form list_tests.sh uses for Cest tests, so CircleCI’s stored timings match the split.
# Codeception writes the attributes in a different order for Cest and Test classes, so
# the name is looked up wherever it is on the line.
# Usage: junit_file_per_test.sh <report.xml>

set -eo pipefail

report=$1
if [ ! -f "$report" ]; then
  echo "No report at $report"
  exit 0
fi

perl -pi -e 'if (/<testcase / && /name="([^"]+)"/) { my $name = $1; s/(file="[^"]+)"/$1:$name"/ }' "$report"
