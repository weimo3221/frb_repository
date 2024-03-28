## 基础AFL系统部署

- AFLplusplus: 集成了QEMU MODE下AFL++的源代码，优化漏洞检测系统中将会基于此代码进行修改
- binwalk_tools: 部署适应多文件系统的binwalk所需提取工具
- firmwares: 测试系统所用固件和两个测试优化AFL效果的程序objdump和readelf
- origin_output: 普通AFL系统输出数据集的存放地，用于输入进Seq2Seq模型
- scripts:
- - auto-extract.sh: 自动提取当前目录固件，build阶段进行
- - fuzz-all.sh: 环境变量`TARGET_FILE`存放待fuzz文件，`TARGET_BIN`存放待fuzz固件，build完成后，进入docker运行
- - switchAFL.sh: 优化漏洞检测系统中进行AFL++的源码切换
- src: 加载模型的相关代码
- vuln_example：漏洞复现实例
- 模型训练：用于训练模型的代码文件夹
- Dockerfile: docker部署命令，主要包含
- - 必要的库的下载，需要联网
- - 环境的迁移，上述所有目录均会移动至docker虚拟机中
- - binwalk环境的配置，批量的固件提取
- - 初始AFL++ QEMU MODE的编译

## 基础AFL系统部署
docker部署命令
```sh
docker build -t ubuntu:v1 .
docker run --privileged -v ./origin_output:/opt/origin_output -it ubuntu:v1 
```

docker run后，会进入docker ubuntu的shell，部署普通AFL系统
```sh
bash fuzz-all.sh
```

结果将会存储于主机`origin_output`文件夹中，将queue目录下的文件用于训练。

## 模型的训练

### 模型训练组成

- csv_output.py：用于生成训练模型的csv数据集，需要先采集基于普通AFL得到的queue文件夹，并命名为queue1、queue2、....这些queue必须保证是同一个程序AFL Fuzz后得到的不同queue
- data_read_dataset.py：csv_output调用的python文件
- train_model.py：训练模型的python文件
- common.py：train_model调用的文件
- modelSet.py：定义模型的文件
- module_data：存放训练好的模型
- csv_data：存放模型训练过程的csv文件，能够看到模型训练的准确率的变化

### 模型训练过程

1. 利用queue文件夹生成训练模型的数据集csv文件

```sh
# 先修改data_read_dataset.py中read_data函数中的directory_path中queue存放的路径，保证queue文件夹命名后缀有数字
directory_path = $queue的路径$ + str(num) + '\\'
# 比如：
directory_path = r'D:\queue' + str(num) + '\\'
# 然后执行下面命令生成csv文件
python csv_output.py
```
**如果生成的csv行数过少，也就是数据集过少，可以针对同一个程序多次AFL Fuzz得到相应的queue，然后设置queue1，queue2，...，然后修改csv_output.py中的数字对应多少个queue文件夹，从而保证数据集够大**

2. 利用csv文件进行模型的训练

```sh
# 训练LSTM+LSTM的seq2seq模型，用的data.csv数据集
python train_model.py -encoder LSTM -decoder LSTM -dataset ./data.csv
# 测试模型是否有效的数据集test_seq_100.csv
python train_model.py -encoder LSTM -decoder LSTM -dataset ./test_seq_100.csv
```

3. 训练完成后我们能够在module_data和csv_data下面看到训练好的模型以及训练时的准确率变化数据

### 已有数据

- data_objdump.csv：objdump的数据集
- data_readelf.csv：readelf的数据集
- data_unrar.csv：unrar的数据集

### 软件库要求

```sh
torch: 1.11.0
torchvision: 0.12.0
timm: 0.9.5
tqdm: 4.65.0
numpy: 1.25.0
pandas: 1.2.4
einops: 0.6.1
```

## 优化漏洞检测系统部署测试

### LSTM+LSTM
训练完成后，如果使用LSTM+LSTM的seq2seq模型进行的训练，将编码模型与解码模型分别保存命名为LLDnet.pth、LLEnet.pth，存放在/opt/src下；
完成后，使用如下命令更换AFL++源码
```sh
bash switchAFL.sh LL
```

### 原始版本
在完成一个程序的fuzz后，如需开启另一个程序的fuzz，可使用如下命令切换回原版
```sh
bash switchAFL.sh origin
```

### 部署优化漏洞检测系统

使用如下命令做部署
```sh
bash fuzz-all.sh
```
