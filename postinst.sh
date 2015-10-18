#!/bin/bash

#########################################################################
# postinst.sh - post installation script for Debian-like systems        #
# (c) 2015, Guillaume Vaillant <guillaume AT theflyingbear DOT net      #
# You can reditribute or modify this under the terms of the MIT Licence #
# which is available in the LICENCE file.                               #
#########################################################################


## Configuration: #######################################################
mirrorsrv="ftp.nl.debian.org"
adminEmail="admin@some.domain"
#########################################################################

cd /

echo "# APT/DPKG"
echo "## Configure..."
cat > /etc/apt/sources.list <<EOF
deb http://${mirrorsrv}/debian/ jessie main non-free contrib
EOF
cat > /etc/apt/sources.list.d/deb_updates.list <<EOF
deb http://${mirrorsrv}/debian/ jessie-updates main contrib non-free
EOF
cat > /etc/apt/sources.list.d/deb_secu.list <<EOF
deb http://security.debian.org/ jessie/updates main contrib non-free
EOF
cat > /etc/apt/apt.conf.d/10norecommends <<EOF
APT::Install-Recommends "0";
EOF
cat > /etc/apt/apt.conf.d/10nosuggests <<EOF
APT::Install-Suggests "0";
EOF

echo "## Update..."
apt-get update
echo "## Extra packages..."
apt-get -y install vim-nox sudo screen ntpdate bsd-mailx logrotate multitail tcpdump nmap lsof bind9-host atop htop iptraf psmisc less mlocate mtr python rsync unzip dnsutils whois cron-apt snmp openssh-server file lockfile-progs openssh-blacklist openssh-blacklist-extra logwatch zsh
echo "## Safe upgrade..."
apt-get -y upgrade

echo "# Shell stuff"
echo "## global color file..."
cat > /etc/bash-colors.inc.sh <<EOF
# Colors definition:
no_color='\\[\\033[0m\\]'
grey='\\[\\033[0;30m\\]'
grey_bold='\\[\\033[1;30m\\]'
red='\\[\\033[0;31m\\]'
red_bold='\\[\\033[1;31m\\]'
green='\\[\\033[0;32m\\]'
green_bold='\\[\\033[1;32m\\]'
blue='\\[\\033[0;34m\\]'
blue_bold='\\[\\033[1;34m\\]'
magenta='\\[\\033[0;35m\\]'
magenta_bold='\\[\\033[1;35m\\]'
cyan='\\[\\033[0;36m\\]'
cyan_bold='\\[\\033[1;36m\\]'
white='\\[\\033[0;37m\\]'
white_bold='\\[\\033[1;37m\\]'
black='\\[\\033[0;38m\\]'
blacl_bold='\\[\\033[1;38m\\]'
yellow='\\[\\033[0;33m\\]'
yellow_bold='\\[\\033[1;33m\\]'
# vim:ft=sh:
EOF

echo "## Bash options for 'root'"
cat > /root/.bashrc <<EOF
# ~/.bashrc: executed by bash(1) for non-login shells.
export LS_OPTIONS='--color=auto'
eval "\`dircolors\`"
alias ls='ls \$LS_OPTIONS'
alias ll='ls \$LS_OPTIONS -l'
alias l='ls \$LS_OPTIONS -lA'
alias grep="grep --color"
# Some more alias to avoid making mistakes:
alias rm='rm -i'
alias cp='cp -i'
alias mv='mv -i'

## Prompt stuff
[ -f /etc/bash-colors.inc.sh ] && source /etc/bash-colors.inc.sh
case "\$TERM" in
xterm*|rxvt*)
	PS1="[\${red_bold}\\u\${no_color}@\\h: \${red_bold}\\w\${no_color}]\${red_bold}\\\\\$ \${no_color}"
	export PROMPT_COMMAND='echo -ne "\\033]0;\${USER}@\${HOSTNAME}: \${PWD}\\007"'
	;;
screen)
	PS1="[\${red_bold}\\u\${no_color}@\\h: \${red_bold}\\w\${no_color}]\${red_bold}\\\\\$ \${no_color}"
	export PROMPT_COMMAND='echo -ne "\ek\$(echo \${USER}@\${HOSTNAME}: \${PWD/\$HOME/\\~})\e\\"'
	;;
linux)
	PS1="[\${red_bold}\\u\${no_color}@\\h: \${red_bold}\\w\${no_color}]\${red_bold}\\\\\$ \${no_color}"
	;;
*)
	PS1="[\\u@\\h: \\w]\\\\\$ "
	;;
esac

# If /tmp is mounted with noexec, point this to another temp folder
export TMPDIR="/var/tmp"

export PS1
EOF
echo "## /etc/skel ..."
cat > /etc/skel/.bashrc <<EOF
[ -z "\$PS1" ] && return
export HISTCONTROL=\$HISTCONTROL\${HISTCONTROL+,}ignoredups
export HISTCONTROL=ignoreboth
shopt -s histappend
shopt -s checkwinsize
if [ -z "\$debian_chroot" ] && [ -r /etc/debian_chroot ]; then
    	debian_chroot=\$(cat /etc/debian_chroot)
	debian_chroot="(\${debian_chroot})"
fi
[ -r /etc/bash-colors.inc.sh ] && source /etc/bash-colors.inc.sh
case "\$TERM" in
xterm*|rxvt*)
	PS1="[\${green_bold}\\u\${no_color}@\\h\${debian_chroot}: \${green_bold}\\w\${no_color}]\${green_bold}\\\\\$ \${no_color}"
	export PROMPT_COMMAND='echo -ne "\033]0;\${USER}@\${HOSTNAME}: \${PWD} \${debian_chroot}\007"'
	;;
screen)
	PS1="[\${green_bold}\\u\${no_color}@\\h\${debian_chroot}: \${green_bold}\\w\${no_color}]\${green_bold}\\\\\$ \${no_color}"
	export PROMPT_COMMAND='echo -ne "\\ek\$(echo \${USER}@\${HOSTNAME}: \${PWD/\$HOME/\\~})\\e\\"'
	;;
linux)
	PS1="[\${green_bold}\\u\${no_color}@\\h\${debian_chroot}: \${green_bold}\\w\${no_color}]\${green_bold}\\\\\$ \${no_color}"
	;;
*)
	PS1="[\\u@\\h\${debian_chroot}: \\w]\\\\\$ "
	;;
esac
export PS1
export LESS="-gIKqRW~"
if [ -x /usr/bin/dircolors ]; then
	eval "\`dircolors -b\`"
	alias ls='ls --color=auto --time-style=long-iso'
	alias grep='grep --color=auto'
	alias fgrep='fgrep --color=auto'
	alias egrep='egrep --color=auto'
	export LESS_TERMCAP_mb=\$'\\E[01;31m'    # begin blinking / bold red
	export LESS_TERMCAP_md=\$'\\E[00;34m'    # begin bold / blue
	export LESS_TERMCAP_me=\$'\\E[0m'        # end mode / no style
	export LESS_TERMCAP_se=\$'\\E[0m'        # end standout-mode / no style
	export LESS_TERMCAP_so=\$'\\E[01;44;33m' # begin standout-mode / bold yellow
	export LESS_TERMCAP_ue=\$'\\E[0m'        # end underline / no style
	export LESS_TERMCAP_us=\$'\\E[00;36m'    # begin underline / cyan
fi
alias screen="screen -AaOq"
alias scat="grep -ve '^\\s*$' -ve '^\\s*#'"
if [ -f /etc/bash_completion ]; then
    . /etc/bash_completion
fi
EOF
chown nobody:nogroup /etc/skel/.bashrc
chmod 0644 /etc/skel/.bashrc

cat > /etc/skel/.zshrc <<EOF
HISTFILE=~/.zsh_history
HISTSIZE=500
SAVEHIST=1000
setopt appendhistory extendedglob notify
unsetopt beep
bindkey -e
zstyle :compinstall filename "~/.zshrc"
autoload -Uz compinit
compinit
if [[ -d "\$HOME/bin" ]] ;
then
	export PATH="\$PATH:\$HOME/bin"
fi
export PATH="\$PATH:/urs/local/bin:/usr/local/sbin"
PS1="%m:%~%# "
case "\$TERM" in
rxvt*|urxvt*|xterm*)
	precmd () { print -Pn "\\e]0;%n@%m: %~\\a" }
	PS1="%U%F{green}%n%u%f@%m: %F{green}%2~%f %S%F{green}%#%f%s "
	;;
screen)
	precmd() {
		print -Pn "\\ek%n@%m: %~ \$a\\e\\\\"
		if [ \$WINDOW -eq 0 ]
		then
			print -Pn "\\e]0;screen: %n@%m\\a"
		fi
	}
	PS1="%U%F{green}%n%u%f@%m: %F{green}%2~%f %S%F{green}%#%f%s "
	;;
*)
	PS1="%n@%m: %2~%# "
	;;
esac
export PS1
export LESS="-gIKqRW~"
export LESS_TERMCAP_mb=\$'\\E[01;31m' # begin blinking / bold red
export LESS_TERMCAP_md=\$'\\E[00;34m' # begin bold / blue
export LESS_TERMCAP_me=\$'\\E[0m' # end mode / no style
export LESS_TERMCAP_se=\$'\\E[0m' # end standout-mode / no style
export LESS_TERMCAP_so=\$'\\E[01;44;33m' # begin standout-mode / bold yellow
export LESS_TERMCAP_ue=\$'\\E[0m' # end underline / no style
export LESS_TERMCAP_us=\$'\\E[00;36m' # begin underline / cyan
alias screen="screen -AaOq"
#alias ls='ls -GT'
alias ls='ls --color=auto --time-style=long-iso'
alias grep='grep --color=auto'
alias fgrep='fgrep --color=auto'
alias egrep='egrep --color=auto'
alias scat="grep -ve '^\\s*$' -ve '^\\s*#'"
EOF
chown nobody:nogroup /etc/skel/.zshrc
chmod 0644 /etc/skel/.zshrc

cat > /etc/skel/.screenrc <<EOF
defutf8 on
startup_message off
caption always
hardstatus alwayslastline '(%{b}%Y-%m-%d %c%{d}) %{r}%u%{d} [%-w %{g}%n %t%{d} %+w] '
EOF
chown nobody:nogroup /etc/skel/.screenrc
chmod 0644 /etc/skel/.screenrc

mkdir /etc/skel/.ssh
chown nobody:nogroup /etc/skel/.ssh
chmod 0600 /etc/skel/.ssh

echo "# Vim"
cat > /etc/vim/vimrc.local <<EOF
" global:
syntax on
set hls
set showmatch
set laststatus=2
set modeline
set modelines=1
" encoding:
set encoding&
set termencoding=
set fileencodings=
set fileencoding&
" title & status line:
set titlestring=%{hostname()}:\\ vim\\ %m\\%F\\%r
set statusline=file:%f\\ [eol:%{&ff}/ft=%Y]\\ POS(l,c,%%):%l,%v,%p%%
set title
" indentation:
set ai
set et
set sw=4
set sts=4
set ts=4
" edit mode:
set cursorline
set nowrap
EOF

echo "# Cron-APT"
cat > /etc/cron-apt/config <<EOF
APTCOMMAND=/usr/bin/apt-get
MAILON="upgrade"
SYSLOGON="error"
DIFFONCHANGES=prepend
EOF
cat > /etc/cron-apt/action.d/3-download <<EOF
autoclean -y
upgrade -d -y -o APT::Get::Show-Upgraded=true -o quiet=2
EOF
cat > /etc/cron-apt/action.d/0-update <<EOF
update -o quiet=2
EOF

echo "# Logrotate"
cat > /etc/logrotate.d/fail2ban <<EOF
/var/log/fail2ban.log {
    compress
    delaycompress
    missingok
    postrotate
	fail2ban-client set logtarget /var/log/fail2ban.log >/dev/null
    endscript
    # if fail2ban doesn't run as root
    # create 640 fail2ban adm
    create 640 root adm
}
EOF
cat > /etc/logrotate.d/rsyslog <<EOF
/var/log/syslog
{
	missingok
	notifempty
	delaycompress
	compress
	postrotate
		invoke-rc.d rsyslog rotate > /dev/null
	endscript
}

/var/log/kern.log
/var/log/lpr.log
/var/log/cron.log
/var/log/debug
/var/log/messages
{
	missingok
	notifempty
	compress
	delaycompress
	sharedscripts
	postrotate
		invoke-rc.d rsyslog rotate > /dev/null
	endscript
}

/var/log/mail.info
/var/log/mail.warn
/var/log/mail.err
/var/log/mail.log
/var/log/daemon.log
/var/log/auth.log
/var/log/user.log
{
	rotate 366
	missingok
	notifempty
	compress
	delaycompress
	sharedscripts
	postrotate
		invoke-rc.d rsyslog rotate > /dev/null
	endscript
}
EOF
cat > /etc/logrotate.conf <<EOF
daily
rotate 31
create
include /etc/logrotate.d
/var/log/wtmp {
    missingok
    monthly
    create 0664 root utmp
    rotate 1
}
/var/log/btmp {
    missingok
    monthly
    create 0660 root utmp
    rotate 1
}
EOF

echo "# Cron"
cat > /etc/cron.d/perso <<EOF
0 0	* * *	root	/usr/sbin/logrotate /etc/logrotate.conf > /dev/null 2> /dev/null || /bin/true
59 23	* * *	root	/usr/sbin/logwatch --mailto ${adminEmail} --output mail > /dev/null 2> /dev/null || /bin/true
EOF
chmod 0755 /etc/cron.d/perso

echo -n > /etc/cron.d/atop
chmod 0444 > /etc/cron.d/atop
echo -e '#!/bin/sh\nexit 0' > /etc/cron.daily/logrotate
chmod 0444 /etc/cron.daily/logrotate
echo -e '#!/bin/sh\nexit 0'> /etc/cron.daily/00logwatch
chmod 0444 /etc/cron.daily/00logwatch

echo "# Default editor/pager"
update-alternatives --set editor /usr/bin/vim.nox
update-alternatives --set pager /bin/less

echo "# SSHd"
cat > /etc/ssh/sshd_config <<EOF
Port 22
Protocol 2
HostKey /etc/ssh/ssh_host_rsa_key
HostKey /etc/ssh/ssh_host_dsa_key
HostKey /etc/ssh/ssh_host_ecdsa_key
UsePrivilegeSeparation yes
KeyRegenerationInterval 3600
ServerKeyBits 768
SyslogFacility AUTH
LogLevel INFO
LoginGraceTime 120
# change after boot
PermitRootLogin yes
StrictModes yes
RSAAuthentication yes
PubkeyAuthentication yes
IgnoreRhosts yes
RhostsRSAAuthentication no
HostbasedAuthentication no
PermitEmptyPasswords no
ChallengeResponseAuthentication no
X11Forwarding no 
X11DisplayOffset 10
PrintMotd no
PrintLastLog yes
TCPKeepAlive yes
AcceptEnv LANG LC_*
Subsystem sftp /usr/lib/openssh/sftp-server
UsePAM yes
ClientAliveInterval 3600
ClientAliveCountMax 0
#AllowUsers user1[@host] [user2@[host]] ...
EOF
/etc/init.d/ssh restart

echo "# Disable some useless services"
for s in atop  mpt-statusd  rsync
do
	echo -n "## disable $s... "
	update-rc.d $s disable &> /dev/null
	if [ $? -eq 0 ] ; then echo OK ; else echo KO ; fi
done

echo "*** DONE ***"
