#!/usr/bin/env bash

# Save our starting location, so we can jump back there later.
scriptdirectory=${PWD}
rulesdirectory='tools/wpscan-semgrep-rules'

# Make sure we have a copy of WPScan's Semgrep rules.
if [ ! -d $scriptdirectory/$rulesdirectory ]
    then
        echo "Cloning WPScan's Semgrep rules repository..."
        git clone --depth=1 git@github.com:Automattic/wpscan-semgrep-rules.git $scriptdirectory/$rulesdirectory
fi

# Run Semgrep
docker run --rm -v "${scriptdirectory}:/src" returntocorp/semgrep semgrep --error --text --metrics=off -c "/src/${rulesdirectory}/audit" $@
