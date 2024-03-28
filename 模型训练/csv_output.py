import data_read_dataset
import csv
import numpy as np

output_dict = dict()
counter = 0
count_all = 0

# 通过调整数字可以读取datax文件夹下的内容
for i in range(1, 2):
    dataset = data_read_dataset.DataInput(i)
    queue = dataset.queue
    for item in queue:
        if queue[item].op == '' or queue[item].pos == '' or queue[item].src == '':
            continue
        if queue[item].op == 14:
            counter += 1
            continue
        if queue[item].op == 15:
            counter += 1
            continue
        if queue[item].op == 16:
            counter += 1
            continue
        cur_src = queue[item].src
        sequence = ','.join([str(x) for x in queue[item].src_data])
        addition = str(queue[item].op) + ',' + str(queue[item].pos)
        if sequence not in output_dict:
            output_dict.setdefault(sequence, []).append(addition)
            count_all += 1
        elif addition not in output_dict[sequence]:
            output_dict.setdefault(sequence, []).append(addition)
            count_all += 1
    print(str(i) + ' over')

data = ['src', 'target']
record = 0

save_len = dict()
for item in output_dict:
    mi = item.count(',') + 1
    if mi not in save_len:
        save_len[mi] = 1
    else:
        save_len[mi] += 1

get = max(save_len.values())
print(save_len.keys())
print(get)
length = 0
#
# for item in save_len:
#     if save_len[item] == get:
#         length = item
#         break
#
# cur_dict = output_dict.keys()
# for item in list(cur_dict):
#     if item.count(',') + 1 != length:
#         output_dict.pop(item)

cur_max = 0
cur_dict = output_dict.keys()

for item in list(cur_dict):
    if item.count(',') + 1 >= 499:
        output_dict.pop(item)
        continue
    if cur_max < item.count(',') + 1:
        cur_max = item.count(',') + 1

print(cur_max)

cur_dict = output_dict.keys()

for item in list(cur_dict):
    mi = item.count(',') + 1
    if mi < cur_max:
        output_dict[item + (',499' * (cur_max - mi))] = output_dict.pop(item)

for item in output_dict:
    value = output_dict[item]
    line = '0'
    for i in range(len(value)):
        line += ',' + str(value[i])
    if record < line.count(',') + 1:
        record = line.count(',') + 1
    data = np.vstack((data, [item, line]))

print('concat over')

data = data.tolist()

for i in range(len(data[1:])):
    mi = data[i + 1][1].count(',') + 1
    if mi < record:
        data[i + 1][1] = data[i + 1][1] + (',499' * (record - mi))

filename = 'data_queue.csv'
with open(filename, 'w', newline='') as file:
    writer = csv.writer(file)
    writer.writerows(data)
