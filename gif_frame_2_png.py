from PIL import Image
import sys
import os

# --- Configuration ---
# You can change this if you always want the output PNG to go elsewhere.
OUTPUT_DIRECTORY = 'extracted_frames'
FRAME_INDEX_TO_EXTRACT = 0  # The first frame (0)

def extract_gif_frame(gif_path, frame_number, output_dir):
    """
    Extracts a specific frame from an animated GIF and saves it as a PNG 
    in a specified output directory.
    """
    if not os.path.exists(gif_path):
        print(f"Error: The input file '{gif_path}' was not found.")
        sys.exit(1)

    # 1. Get the base name (e.g., 'my_animation') from the input path
    base_name = os.path.splitext(os.path.basename(gif_path))[0]
    
    # 2. Construct the full output path
    # Example: 'extracted_frames/my_animation_frame_0.png'
    output_filename = f"{base_name}_frame_{frame_number}.png"
    output_path = os.path.join(output_dir, output_filename)
    
    # 3. Create the output directory if it doesn't exist
    os.makedirs(output_dir, exist_ok=True)
    
    try:
        # 4. Open the GIF file
        img = Image.open(gif_path)

        # 5. Go to the specified frame index
        try:
            img.seek(frame_number)
        except EOFError:
            print(f"Error: Frame number {frame_number} is out of range for the GIF.")
            print(f"The GIF only has {img.n_frames} frames (0 to {img.n_frames - 1}).")
            return

        # 6. Save the current frame as a PNG
        # 
        img.save(output_path, 'PNG')

        print(f"\n✅ Success!")
        print(f"   Input GIF: {gif_path}")
        print(f"   Extracted Frame: {frame_number}")
        print(f"   Output PNG: {output_path}")

    except Exception as e:
        print(f"An unexpected error occurred: {e}")

# --- Main execution block ---
if __name__ == "__main__":
    # Check if a file path was provided on the command line
    if len(sys.argv) < 2:
        print("\nUsage: python script_name.py <path_to_your_animated_gif>")
        print("Example: python extract.py my_cool_animation.gif")
        sys.exit(1)

    # The first argument (index 0) is the script name itself
    # The second argument (index 1) is the GIF file path
    gif_file_path = sys.argv[1]
    
    extract_gif_frame(gif_file_path, FRAME_INDEX_TO_EXTRACT, OUTPUT_DIRECTORY)