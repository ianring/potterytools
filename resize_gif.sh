#!/bin/bash

# Check if correct number of arguments are passed
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <input_file.gif>"
    exit 1
fi

INPUT_FILE=$1


# Check if input file exists
if [ ! -f "$INPUT_FILE" ]; then
    echo "Error: Input file '$INPUT_FILE' not found."
    exit 1
fi

# 3. Generate Output Filename
# ${INPUT_FILE%.*} removes the extension (everything after the last dot)
# ${INPUT_FILE##*.} extracts just the extension
OUTPUT_FILE="${INPUT_FILE%.*}-thumb.${INPUT_FILE##*.}"

echo "Processing: $INPUT_FILE"
echo "Target:     $OUTPUT_FILE"



echo "Resizing $INPUT_FILE to 200x200..."

# # Resize command
# # -coalesce: Reconstructs the animation sequences to full frames (avoids artifacts)
# # -resize: Resizes the image
# # -layers Optimize: Optimizes the output to reduce file size
# magick convert "$INPUT_FILE" -coalesce -resize 200x200 -layers Optimize "$OUTPUT_FILE"

# if [ $? -eq 0 ]; then
#     echo "Success! Saved as $OUTPUT_FILE"
# else
#     echo "An error occurred during processing."
# fi


# -i: Input file
# -vf "scale=200:-1": Resizes width to 200, keeps aspect ratio for height
# -vf "scale=200:200": Forces 200x200 (might stretch)
# split/palettegen: Generates a custom color palette for the new size to ensure high quality
ffmpeg -i "$INPUT_FILE" -vf "scale=200:200:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" -y "$OUTPUT_FILE"