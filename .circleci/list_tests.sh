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
# by prefix, so a method whose name starts another method’s name is listed as a whole
# file instead, or the shorter name would run on two containers. A Cest with @depends is
# listed whole too, so a test and the one it depends on stay on one container.

set -eo pipefail

dir=$1
group=$2

# true when another public method of the file starts with the given name
has_longer_sibling() {
  awk -v name="$2" '
    match($0, /^[[:space:]]*public function [A-Za-z][A-Za-z0-9_]*/) {
      line = substr($0, RSTART, RLENGTH); sub(/.*function /, "", line)
      if (line != name && index(line, name) == 1) { found = 1; exit }
    }
    END { exit !found }
  ' "$1"
}

# prints a Cest as file:method entries, or as the file when its method names overlap
list_cest() {
  if grep -q '@depends' "$1"; then
    echo "$1"
    return
  fi
  awk -v file="$1" '
    match($0, /^[[:space:]]*public function [A-Za-z][A-Za-z0-9_]*/) {
      name = substr($0, RSTART, RLENGTH); sub(/.*function /, "", name)
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

list_file() {
  if [[ "$1" == *Cest.php ]]; then list_cest "$1"; else echo "$1"; fi
}

if [[ -z "$group" ]]; then
  find "$dir" -name '*Test.php' -o -name '*Cest.php' | sort | while read -r file; do
    list_file "$file"
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
    pending && match($0, /^[[:space:]]*public function [A-Za-z][A-Za-z0-9_]*/) {
      name = substr($0, RSTART, RLENGTH); sub(/.*function /, "", name)
      print file ":" name; pending = 0
    }
  ' "$file" | while read -r entry; do
    if [[ "$entry" == "WHOLE" ]]; then
      list_file "$file"
    elif has_longer_sibling "$file" "${entry#*:}"; then
      echo "list_tests.sh: ${entry#*:} starts another method name in $file, listing the whole file" >&2
      echo "$file"
    else
      echo "$entry"
    fi
  done
done
