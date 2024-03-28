### 已有数据

- LLEnet_unrar.pth：基于unrar数据集训练得到的LSTM+LSTM模型的Encoder
- LLDnet_unrar.pth：基于unrar数据集训练得到的LSTM+LSTM模型的Decoder
- TTnet_unrar.pth：基于unrar数据集训练得到的Transformer+Transformer模型
- LLEnet_elf.pth：基于readelf数据集训练得到的LSTM+LSTM模型的Encoder（需要Pytorch有cuda，也可以重新训练）
- LLDnet_elf.pth：基于readelf数据集训练得到的LSTM+LSTM模型的Decoder（需要Pytorch有cuda，也可以重新训练）
- TTnet_elf.pth：基于readelf数据集训练得到的Transformer+Transformer模型（需要Pytorch有cuda，也可以重新训练）
- LLEnet_obj.pth：基于objdump数据集训练得到的LSTM+LSTM模型的Encoder
- LLDnet_obj.pth：基于objdump数据集训练得到的LSTM+LSTM模型的Decoder
- TTnet_obj.pth：基于objdump数据集训练得到的Transformer+Transformer模型
- module-LL.py：用于部署在AFL上LSTM+LSTM模型的python文件（使用时记得修改模型的路径文件）
- module-TT.py：用于部署在AFL上Transformer+Transformer模型的python文件（使用时记得修改模型的路径文件）

**如果遇到有模型无法使用的情况，有可能是训练模型时的环境不同导致的，可以按照模型训练的步骤重新训练**

