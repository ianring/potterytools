#!/bin/bash

# --- Configuration ---
# Source directory on the local machine
SOURCE_PATH="/root/potterytools/carousel/gif/"
# Pattern to match files (all .gif files in the source directory)
FILE_PATTERN="*.gif"

# Destination path on the remote machine
# Format: [user]@[host]:[remote_directory]
DESTINATION_PATH="root@ianring.com:/var/www/darkware.shop/public_html/pieces/images/carousel/gif/"

# --- Execution ---
echo "Starting secure copy operation..."
echo "Source: ${SOURCE_PATH}${FILE_PATTERN}"
echo "Destination: ${DESTINATION_PATH}"
echo "-----------------------------------"

# The 'scp' command:
# - 'p' preserves modification times, access times, and modes.
# - 'r' would be for recursive copy of directories (not needed here since we're matching files).
# - The command copies all files matching the pattern from the source to the destination.
scp -p "${SOURCE_PATH}${FILE_PATTERN}" "${DESTINATION_PATH}"

# --- Status Check ---
if [ $? -eq 0 ]; then
    echo "-----------------------------------"
    echo "✅ File copy completed successfully!"
else
    echo "-----------------------------------"
    echo "❌ An error occurred during the file copy."
    echo "Please check the paths, permissions, and SSH connectivity."
fi