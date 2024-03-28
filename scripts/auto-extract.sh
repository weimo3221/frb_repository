#!/bin/bash

for bin in `ls` 
do
    if [[ "$bin" = *.bin ]];then   
	    binwalk -Me $bin
    fi
done

# filelist=`find ./ -name 'squashfs-root'`
# fs=()
# for file in $filelist
# do
#     fs+=($file)
# done
# echo ${fs[@]}
# tar czvf all.tar.gz ${fs[@]}
