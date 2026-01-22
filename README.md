# potterytools
Tools for managing altpottery.com and darkware.shop

When a new piece is made:

1. Go to the studio, put it on the carousel and get a video of it rotating at least one full rotation. This will be an MP4

2. Put the original video in /Product Shots/carousel/originals

3. Open that video in Motion 5. Crop it to the square aspect ratio, 2000x2000.

4. Adjust the start and end of the video so it loops perfectly. Try to make it so the first frame is a good one for the still shot.

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

11. deploy the image to the website with this command:
```
./upload_to_website.sh
```
that will copy it over to the ianring.com server at `/var/www/darkware.shop/public_html/pieces/images/carousel/png`

12. run this script to copy over the large version of the animated GIF
```
./deploy.php
```

13. Add the two images to the database

```
INSERT INTO `altpottery_images` (`piece`, `url`, `thumburl`) VALUES ('29', '/pieces/images/carousel/gif/00000.gif', '/pieces/images/carousel/png/00000-thumb.png');
```


TODO: make a script that does all of this fucking stuff all in one go



== new in progress photos ==

put the photos into appropriate folders of 

1. ssh root@i-a-n.ca

# 1. Force ownership of the entire data directory to the PHP user
chown -R apache:apache /data/imagetool

# 2. Set directory permissions (775 allows apache to create folders/files)
find /data/imagetool -type d -exec chmod 775 {} +

# 3. Set file permissions (664 allows apache to edit/delete images)
find /data/imagetool -type f -exec chmod 664 {} +

# 4. Refresh the SELinux context for Fedora
chcon -R -t httpd_sys_rw_content_t /data/imagetool

