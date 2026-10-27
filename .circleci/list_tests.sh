#!/bin/bash

# Lists tests as Codeception group-file entries, one per line, for `circleci tests run`
# to split over the job’s containers. Usage: list_tests.sh <tests directory> [group]
#
# Without a group, every test file in the directory is listed. With a group, only the
# tests in it: the file when the whole class is in the group, otherwise file:method for
# each method that is (passing --group to Codeception instead would run every grouped
# test on each container).
#
# A Cest file is listed as one file:method entry per test, so the split balances the
# acceptance suite by test rather than by file. Codeception matches a file:method entry
# by prefix, so a Cest whose method names are prefixes of each other is listed as a
# whole file, or the shorter name would run on two containers.

set -eo pipefail

dir=$1
group=$2

# prints a Cest as file:method entries, or as the file when its method names overlap
list_cest() {
  awk -v file="$1" '
    match($0, /public function [A-Za-z][A-Za-z0-9_]*/) {
      name = substr($0, RSTART + 16, RLENGTH - 16)
      if (name ~ /^_/) next
      names[++count] = name
    }
    END {
      for (i = 1; i <= count; i++)
        for (j = 1; j <= count; j++)
          if (i != j && index(names[j], names[i]) == 1) { print file; exit }
      for (i = 1; i <= count; i++) print file ":" names[i]
    }
  ' "$1"
}

if [[ -z "$group" ]]; then
  find "$dir" -name '*Test.php' -o -name '*Cest.php' | sort | while read -r file; do
    if [[ "$file" == *Cest.php ]]; then list_cest "$file"; else echo "$file"; fi
  done
  exit 0
fi

# grep exits 1 when nothing matches; an empty list is a valid result
{ grep -rlw "$dir" -e "@group $group" || true; } | sort | while read -r file; do
  awk -v file="$file" -v group="$group" '
    # an @group before the class line puts the whole class in the group
    !seen_class && $0 ~ ("@group[[:space:]]+" group "([[:space:]]|$)") { print "WHOLE"; exit }
    /^(abstract |final )?class / { seen_class = 1 }
    seen_class && $0 ~ ("@group[[:space:]]+" group "([[:space:]]|$)") { pending = 1 }
    pending && match($0, /function [A-Za-z][A-Za-z0-9_]*/) { print file ":" substr($0, RSTART + 9, RLENGTH - 9); pending = 0 }
  ' "$file" | while read -r entry; do
    if [[ "$entry" == "WHOLE" ]]; then
      if [[ "$file" == *Cest.php ]]; then list_cest "$file"; else echo "$file"; fi
    else
      echo "$entry"
    fi
  done
done
