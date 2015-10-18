#!/bin/bash

#########################################################################
# livepush.sh - stream test pattern to a RTMP capable server            #
# (c) 2015, Guillaume Vaillant <guillaume AT theflyingbear DOT net      #
# You can reditribute or modify this under the terms of the MIT Licence #
# which is available in the LICENCE file.                               #
#########################################################################

# TODO:
# - stream to more than one origin server
# - test input
# - warning about the need of an up to date version of ffmpeg

usage() {
	cat <<EOF
This script will use ffmpeg to stream a single image (with timestamp)
with a blank sound (mono, 32kb/s, 22050Hz).

Usage:
  $0 [-?] -i sourceImage -o rtmpCapableHost [-a app -s streamName] \
    [ -x videoSize -p frameRate -r videoBitRate] [-h|-f]
  -u show this message
  -i path to some local image
  -o origin server
  -a application name, defaults to live
  -s stream name, defaults to a serie of digits
  -x videoSize: WidthxHeight, defaults to 320x240
  -p frameRate: number of frames per seconds, defaults to 23.97
  -r videoBitrate: in kilobits/s, defaults to 250kbs
  -h stream as H264+AAC, this is the default
  -f stream as simple flash
EOF
}


image=""
origin=""
app="live"
stream="${RANDOM}$(date +%s)${RANDOM}"
size="320x240"
fr=23.97
vr=250

hq=1
while getopts 'i:o:a:s:x:p:r:hf?' OPTION
do
	case "$OPTION" in
	i)
		image="${OPTARG}"
		;;
	o)
		origin="${OPTARG}"
		;;
	a)
		app="${OPTARG}"
		;;
	s)
		stream="${OPTARG}"
		;;
	x)
		size="${OPTARG}"
		;;
	p)
		fr=${OPTARG}
		;;
	r)
		vr=${OPTARG}
		;;
	h)
		hq=1
		;;
	f)
		hq=0
		;;
	?)
		usage
		exit 0
		;;
	*)
		echo "Unknown parameter: -${OPTION} ${OPTARG}"
		usage
		exit 1
		;;
	esac
done

echo "## stream still ${image} to rtmp://${origin}/${app}/${stream} (${size}/${fr:-23.97}fps/${vr:-250}kbs)"

t=$((vr * 50)) # (vr*1000)*(5/100)
minr=$((vr * 1000 - t))
maxr=$((vr * 1000 + t))

vcodec="-c:v libx264"
vcopts='-pix_fmt yuv420p -preset faster -tune stillimage -profile:v baseline -level 30 -crf 23'
aspec="-c:a libfaac -ac 1 -ar 22050 -b:a 32000"
sspec="-b:v ${vr:-250}000 -minrate $minr -maxrate $maxr -bt $t -bufsize $((maxr + t)) -muxrate $maxr"

if [ $hq -eq 0 ]
then
	aspec="-c:a nellymoser -ac 1 -ar 22050 -b:a 32000"
	vcodec="-c:v flv1"
	vcopts='-pix_fmt yuv420p'
	sspec="-b:v ${vr:-250}000"
fi

gopts='-v warning -report -stats'
src="-loop 1 -r 30 -i '${image}' -ar 48000 -ac 2 -f s16le -i /dev/zero"
itext="drawtext=fontfile=/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans-Bold.ttf:fontsize=32:fontcolor=white:box=1:boxcolor=black:text='%{localtime}':x=(w-text_w)/2:y=(h-text_h-line_h)/2"
vspec="-s ${size} -r ${fr}"

ffmpeg $gopts $src -vf "${itext}" $vcodec $vcopts $vspec $sspec -f flv -y "rtmp://${origin}/${app}/${stream}"
