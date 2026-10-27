#!/bin/bash

# Lists the tests of a Codeception group as group-file entries, one per line: the
# file when the whole class is in the group, otherwise file:method for each method
# that is. `circleci tests run` splits that list over the job’s containers; passing
# --group to Codeception instead would run every grouped test on each of them.
# Usage: list_group_tests.sh <tests directory> <group>

set -eo pipefail

dir=$1
group=$2

# grep exits 1 when nothing matches; an empty list is a valid result
{ grep -rlw "$dir" -e "@group $group" || true; } | sort | while read -r file; do
  awk -v file="$file" -v group="$group" '
    # an @group before the class line puts the whole class in the group
    !seen_class && $0 ~ ("@group[[:space:]]+" group "([[:space:]]|$)") { print file; exit }
    /^(abstract |final )?class / { seen_class = 1 }
    seen_class && $0 ~ ("@group[[:space:]]+" group "([[:space:]]|$)") { pending = 1 }
    pending && match($0, /function [A-Za-z][A-Za-z0-9_]*/) { print file ":" substr($0, RSTART + 9, RLENGTH - 9); pending = 0 }
  ' "$file"
done
