#!/usr/bin/env sh

# ensure this is run on MacOS
if [ "$(uname -s)" != "Darwin" ]; then
  (>&2 echo "This script can only be run on MacOS.")
  exit 1
fi

# ensure root privileges
if [ $EUID -ne 0 ]; then
  (>&2 echo "This script must be run with sudo.")
  exit 1
fi

# add shared file settings for current user to /etc/exports (if not set yet)
LINE="/System/Volumes/Data/Users -alldirs -mapall="$SUDO_USER":$(id -gn "$SUDO_USER") localhost"
FILE=/etc/exports
grep -qF -- "$LINE" "$FILE" || echo "$LINE" >> "$FILE"

# add NFS settings to /etc/nfs.conf (if not set yet)
LINE="nfs.server.mount.require_resv_port = 0"
FILE=/etc/nfs.conf
grep -qF -- "$LINE" "$FILE" || echo "$LINE" >> "$FILE"

# restart NFS server
nfsd restart

cat <<EOT
NFS volume sharing is set up. Recreate your containers and volumes using:
    cp docker-compose.override.macos-sample.yml docker-compose.override.yml

    docker-compose down -v --remove-orphans
    docker-compose up -d
EOT
