#!usr/bin/bash
iptables -t nat -I PREROUTING --src 0/0 --dst EC2IPAddress -p tcp --dport EC2PortNum -j REDIRECT --to-ports 1337
