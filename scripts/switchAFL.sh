#!/bin/bash

if [ $# -eq 0 ]; then
    echo "未提供命令行参数。"
    exit 1
fi

cd /opt/AFLplusplus
if [ "$1" = "LL" ]; then
    mv /opt/src/afl-fuzz-one-pro.c ./src/afl-fuzz-one.c
    new_content="  int check_system = system(\"cd /opt/src && python moduleload-LL.py\");"
    sed -i "618s/.*/${new_content}/" "./src/afl-fuzz-one.c"
fi
if [ "$1" = "origin" ]; then
    mv /opt/src/afl-fuzz-one.c ./src/afl-fuzz-one.c
fi

make && make install
cd /opt/firmwares