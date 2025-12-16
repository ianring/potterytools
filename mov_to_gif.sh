#!/bin/bash

# --- Configuration ---

# $1 will be replaced by the first argument passed when running the script
INPUT_FILE="$1" 

# **HARD-CODED OUTPUT DIRECTORY**
OUTPUT_PATH="/root/potterytools/carousel/gif" 

# Check if the input file argument was provided
if [ -z "$INPUT_FILE" ]; then
    echo "Error: Please provide the path to the input .mov file as an argument."
    echo "Usage: $0 /path/to/video.mov"
    exit 1
fi

# Check if the input file exists
if [ ! -f "$INPUT_FILE" ]; then
    echo "Error: Input file '$INPUT_FILE' not found!"
    exit 1
fi

# --- Automatic Filename Calculation ---

# 1. Get the filename without the path (e.g., 1.mov)
INPUT_FILENAME=$(basename "$INPUT_FILE")

# 2. Replace the .mov extension with .gif (e.g., 1.gif)
OUTPUT_FILENAME=$(echo "$INPUT_FILENAME" | sed 's/\.mov$//i').gif

# 3. Construct the final output path using the hard-coded directory
OUTPUT_FILE="$OUTPUT_PATH/$OUTPUT_FILENAME"

# --- FFmpeg Settings ---
TARGET_WIDTH="1024"
FPS="15"
FILTERS="fps=$FPS,scale=$TARGET_WIDTH:-1:flags=lanczos"

# --- Execution ---
echo "--- Starting Conversion ---"
echo "Input: $INPUT_FILE"
echo "Output: $OUTPUT_FILE"

# Ensure the output directory exists
echo "Ensuring output directory exists: $OUTPUT_PATH"
mkdir -p "$OUTPUT_PATH"

# 1. Create a custom color palette from the video
echo "Generating custom color palette..."
ffmpeg -i "$INPUT_FILE" -vf "$FILTERS,palettegen" -y /tmp/palette.png

# 2. Generate the GIF using the custom palette
echo "Converting video to GIF..."
ffmpeg -i "$INPUT_FILE" -i /tmp/palette.png -lavfi "$FILTERS [x]; [x][1:v] paletteuse" -y "$OUTPUT_FILE"

echo "--- Conversion Complete! ---"
echo "File saved to: $OUTPUT_FILE"