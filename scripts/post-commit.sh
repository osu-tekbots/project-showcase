#!/bin/sh
#
# This script enforces some rules before changes are committed. For now, it just
# sets the file permissions for all files and directories so they are accessible
# from the browser. Copy this file as `pre-commit` into `.git/hooks` and make it
# executable.
#
clear

echo "Starting post-commit..."

echo "Making sure permissions for files and directories are public"

for f in $(find /nfs/ca/info/eecs_www/project-showcase ! -path "*/config.php*" ! -path "*/scripts/*" ! -path "*/.*" ! -path "*.md"); 
do
    # if [ "$f" = "." ] || [ "$f" = ".." ]; then
    #     continue
    # fi

    FILE_PERMISSIONS=$(stat -c "%a" "$f")

    if [ -f "$f" ] && [ "$FILE_PERMISSIONS" != '664' ]; then
        echo
        echo "ERROR: Found file '$f' with incorrect permissions '$FILE_PERMISSIONS'"
        echo "Would you like to update this file's permissions to rw-rw-r--?"
        read -p "y/n: " yn < /dev/tty

        if [ "$yn" = "y" ]; then
            chmod 664 "$f"
        else
            echo "Run 'chmod 664 \"$f\"' from the repository root before committing."
        fi
    fi
done

# Used to propagate the exit status from the while loop for the whole script
status=$?
echo "Post-commit done."
exit $status