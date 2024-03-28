FROM ubuntu:focal

COPY sources.list /etc/apt/sources.list
# COPY mylib/* /
# RUN dpkg -i /libssl1.1_1.1.1f-1ubuntu2_amd64.deb
# RUN dpkg -i /libssl-dev_1.1.1f-1ubuntu2_amd64.deb
# RUN dpkg -i /ca-certificates_20190110ubuntu1_all.deb
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update

RUN apt install python3 python3-pip binwalk qemu-user -y
RUN apt install build-essential liblzma-dev liblzo2-dev zlib1g-dev wget git ninja-build pkg-config -y
RUN apt install mtd-utils gzip bzip2 tar arj lhasa p7zip p7zip-full cabextract cramfsswap squashfs-tools sleuthkit default-jdk lzop srecord libglib2.0-dev libpixman-1-dev -y

RUN pip3 config set global.index-url https://pypi.tuna.tsinghua.edu.cn/simple

COPY ./ /opt/
RUN echo "gitdir: ../../.git/modules/qemu_mode/qemuafl" > /opt/AFLplusplus/qemu_mode/qemuafl/.git
WORKDIR /opt/binwalk_tools/sasquatch
RUN bash build.sh
WORKDIR /opt/binwalk_tools/yaffshiv
RUN python3 setup.py install
RUN pip3 install jefferson ubi_reader

RUN cp /opt/scripts/* /opt/firmwares
WORKDIR /opt/firmwares
RUN bash auto-extract.sh

WORKDIR /opt/AFLplusplus
RUN make && make install
WORKDIR /opt/AFLplusplus/qemu_mode
ENV NO_CHECKOUT True
ENV CPU_TARGET arm
RUN sh build_qemu_support.sh
RUN cp ../afl-qemu-trace /usr/local/bin

WORKDIR /opt/firmwares
CMD bash
