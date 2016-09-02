#!/bin/bash

#########################################################################
# mssh - connect to a remote via one or more jump host                  #
# (c) 2015, Guillaume Vaillant <guillaume AT theflyingbear DOT net      #
# You can reditribute or modify this under the terms of the MIT Licence #
# which is available in the LICENCE file.                               #
#########################################################################


if [ -z "$1" ]
then
	echo "Usage: $0 '[user1@]host1[:port1],[user2]@host2[:port2],...'"
	exit 1
fi

c="$1"
d="$(echo "$c" | sed -e 's/:\([0-9][0-9]*\)/ -p \1 /g')"
e="$(echo "$d" | sed -e 's/\([^@:,][^@:,]*\)@/ -l \1 /g')"
f="$(echo "$e" | sed -e 's/,/ ssh -t -A /g')"

echo ssh -t -A $f
