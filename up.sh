#!/bin/bash

# run this locally, to push new scripts to the server.

rsync -avh -e ssh /Users/iring/Documents/Repos/github/ianring/potterytools/*.py root@138.197.153.40:/root/potterytools
rsync -avh -e ssh /Users/iring/Documents/Repos/github/ianring/potterytools/*.sh root@138.197.153.40:/root/potterytools

exit