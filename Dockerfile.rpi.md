# Dockerfile.rpi:

Creates a docker image to build package on raspbian

## Build:

`docker build -f Dockerfile.rpi -t local/raspbian:jessie .`

## Use:

```
user@machine: ~ $ docker run --name rpi-builder -v ~/build:/usr/src --rm -i -t local/raspbian:jessie
root@fad342776762:/usr/src# uname -a
Linux fad342776762 3.16.0-4-amd64 #1 SMP Debian 3.16.7-ckt25-2+deb8u3 (2016-07-02) armv7l GNU/Linux
root@fad342776762:/usr/src# dpkg --print-architecture
armhf
```
