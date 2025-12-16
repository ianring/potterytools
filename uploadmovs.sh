#!/bin/bash

# run this locally, to push new scans to the rendering server

rsync -avh -e ssh /Users/iring/Documents/ALTPOTTERY/Product\ Shots/carousel/mov root@i-a-n.ca:/root/potterytools/carousel

exit
