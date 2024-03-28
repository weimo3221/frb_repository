#!/bin/bash

if [ ! -n "$TARGET_FILE" ]; then
    TARGET_FILE=unrar
fi
if [ ! -n "$TARGET_BIN" ]; then
    TARGET_BIN=123
fi
cur_root=`pwd`
dirlist=`find ./ -type d -name '*root*' | grep $TARGET_BIN`
echo core >/proc/sys/kernel/core_pattern

for dir in $dirlist 
do
    cd $dir
    export QEMU_LD_PREFIX=.
    chmod a-w .
    find . -type f -exec file {} \; | grep "ARM" | cut -d: -f1 | while read -r file; do
        # file_name=$(echo "$file" | tr '/' '-')
        fuzz_in="${file}_fuzz-in"
        fuzz_out="/opt/origin_output"
        mkdir -p $fuzz_in
        mkdir -p $fuzz_out
        rm -rf "${fuzz_out}/*"
        if grep -q "$TARGET_FILE" "$file"; then
            afl-fuzz -D -i $fuzz_in -o $fuzz_out -Q "$file" e @@
        fi
    done
    cd $cur_root
done
