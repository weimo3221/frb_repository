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

~~~ python
# 先修改data_read_dataset.py中read_data函数中的directory_path中queue存放的路径，保证queue文件夹命名后缀有数字
directory_path = $queue的路径$ + str(num) + '\\'
# 比如：
directory_path = r'D:\queue' + str(num) + '\\'
# 然后执行下面命令生成csv文件
python csv_output.py
~~~

2. 利用csv文件进行模型的训练

~~~python
# 训练LSTM+LSTM的seq2seq模型，用的data.csv数据集
python train_model.py -encoder LSTM -decoder LSTM -dataset ./data.csv
# 测试模型是否有效的数据集test_seq_100.csv
python train_model.py -encoder LSTM -decoder LSTM -dataset ./test_seq_100.csv
~~~

3. 训练完成后我们能够在module_data和csv_data下面看到训练好的模型以及训练时的准确率变化数据

### 已有数据

- data_objdump.csv：objdump的数据集
- data_readelf.csv：readelf的数据集
- data_unrar.csv：unrar的数据集

### 软件库要求

~~~python
torch: 1.11.0
torchvision: 0.12.0
timm: 0.9.5
tqdm: 4.65.0
numpy: 1.25.0
pandas: 1.2.4
einops: 0.6.1
~~~

