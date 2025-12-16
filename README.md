# potterytools
Tools for managing altpottery.com and darkware.shop

When a new piece is made:

1. Go to the studio, put it on the carousel and get a video of it rotating at least one full rotation. This will be an MP4

2. Put the original video in /Product Shots/carousel/originals

3. Open that video in Motion 5. Crop it to the square aspect ratio, 2000x2000.

4. Adjust the start and end of the video so it loops perfectly

5. Save/Export it as a .mov and put it locally in /Product Shots/carousel/mov

6. locally, in the potterytools project (here!) run 
```
./uploadmovs.sh
```
to copy the MOV videos from the local folder to the i-a-n.ca server

7. ssh in to i-a-n.ca

8. Run this command:
```
/root/potterytools/mov_to_gif.sh /root/potterytools/carousel/mov/00000.mov  
```
to convert that MOV into an animated gif. It will go in the `/root/potterytools/carousel/gif` folder

9. run this command:
```
./resize_gif.sh /root/potterytools/carousel/gif/00000.gif
```
to make a thumbnail version of the gif and save it in `/root/potterytools/carousel/gif/00000-thumb.gif`

10. Run this command:
```
python3 gif_frame_2_png.py /root/potterytools/carousel/gif/00000-thumb.gif
```


