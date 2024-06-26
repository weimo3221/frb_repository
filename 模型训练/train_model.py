import pandas as pd
import numpy as np
from common import MyDataset, get_dataloader, train_LSTM
import torch
from torch.utils.data import random_split
from modelSet import LstmEncoder, LstmDecoder
import matplotlib.pyplot as plt
from common import pad
import argparse
import random


# data_size: 数据集的大小
# T是seq_len的长度， T1是src的seq_len长度， T2是tgt的seq_len的长度
# B: batch_size
# H: hidden_dim
# E: embedding_dim
# V: vocab_size
# 模型的代码定义


def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument("--encoder", "-encoder", type=str, default="LSTM", help="LSTM")
    parser.add_argument("--decoder", "-decoder", type=str, default="LSTM", help="LSTM")
    parser.add_argument("--dataset", "-dataset", type=str, default=r"./data_2.csv", help="csv文件路径")
    parser.add_argument("--cuda", "-cuda", type=int, default=0, help="选择cuda的类型")
    args = parser.parse_args()
    return args


if __name__ == "__main__":
    args = parse_args()
    # 读取csv文件，并构造出我们想要的数据集
    # 在这里设置一些初始值
    max_length = 450
    batch_size = 16
    device = torch.device("cpu")
    print(f"训练的设备为cpu")
    df = pd.read_csv(args.dataset)
    src = df["src"]
    target = df["target"]
    for i in range(len(src)):
        src[i] = src[i].split(',')
        src[i] = [int(x) for x in src[i]]
        target[i] = target[i].split(',')
        target[i] = [int(x) for x in target[i]]
    src = list(src)
    target = list(target)

    data_x = np.array(src, dtype=np.float32)
    data_y = np.array(target, dtype=np.float32)
    print(data_x.shape)
    print(data_y.shape)
    # data_x shape: (data_size, seq_len)
    # data_y shape: (data_size, actual_len)
    dataset = MyDataset(data_x, data_y, device)
    train_dataset, test_dataset = random_split(dataset,
                                               [int(len(dataset) * 0.9), len(dataset) - int(len(dataset) * 0.9)])
    # 这下面设置的train_dataset与test_dataset其实是为了测试来使用的
    # train_dataset = MyDataset(data_x, data_y)
    # test_dataset = MyDataset(data_x[int(len(data_x)*0.1):, :], data_y[int(len(data_y)*0.1):, :])
    # 将数据进行加载最终得到训练集和测试集
    # 这里的32设置的是batch_size
    train_loader = get_dataloader(batch_size, train_dataset)
    test_loader = get_dataloader(batch_size, train_dataset)

    # 选择所想要进行训练的模型种类
    print("双向LSTM+单向LSTM")
    # model_kind = int(input("请选择你想要采用的Encoder以及Decoder:").strip())

    # 开始进行模型的训练和预测
    csv_name = ''  # csv文件的名字
    if args.encoder == "LSTM" and args.decoder == "LSTM":
        vocab_size = 500
        embedding_dim = 128
        hidden_dim = 128
        layer_dim = 1
        output_dim = vocab_size
        enc_module = LstmEncoder(vocab_size, embedding_dim, hidden_dim, layer_dim).to(device)
        dec_module = LstmDecoder(vocab_size, embedding_dim, hidden_dim, layer_dim, output_dim, embedding_dim).to(device)
        enc_module, dec_module, train_process = train_LSTM(enc_module, dec_module, train_loader, test_loader,
                                                           lr=0.01, num_epochs=500, mode=0, device=device)
        torch.save(enc_module, './module_data/LLEnet.pth')
        torch.save(dec_module, './module_data/LLDnet.pth')
        csv_name = './csv_data/LL_process.csv'
        print("Save in:", './module_data/LLEnet.pth and ./module_data/LLDnet.pth')
    else:
        train_process = None

    # 保存训练过程
    train_process.to_csv(csv_name, index=False)
    # 可视化模型训练过程中
    plt.figure(figsize=(18, 6))
    plt.subplot(1, 2, 1)
    plt.plot(train_process.epoch, train_process.train_loss_all,
             "r.-", label="Train loss")
    plt.plot(train_process.epoch, train_process.val_loss_all,
             "bs-", label="Val loss")
    plt.legend()
    plt.xlabel("Epoch number", size=13)
    plt.ylabel("Loss value", size=13)
    plt.subplot(1, 2, 2)
    plt.plot(train_process.epoch, train_process.train_acc_all,
             "r.-", label="Train acc")
    plt.plot(train_process.epoch, train_process.val_acc_all,
             "bs-", label="Val acc")
    plt.xlabel("Epoch number", size=13)
    plt.ylabel("Acc", size=13)
    plt.legend()
    plt.show()
