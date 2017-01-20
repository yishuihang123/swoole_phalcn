#!/bin/bash
#服务pid文件
pidFile="/var/server_pid";
#php=`/usr/bin/php`
# 开启服务
function start(){
        php ./public/index.php
	printf $?
	if [ $? == 0 ]; then
		printf "\server start OK\r\n"
		return 0
	else
		printf "\server start FAIL\r\n"
		return 1
	fi
}

# 关闭服务
function stop(){
	$(ps aux  | grep "$pidFile" |grep -v "grep "| awk '{print $2}'    | xargs  kill -9)    
	PROCESS_NUM2=$(ps aux  | grep "$pidFile" |grep -v "grep "| awk '{print $2}'   | wc -l )    
	if [ $PROCESS_NUM2 == 0 ]; then
		printf "qserver stop OK\r\n"
		return 0
	else
		printf "server stop FAIL\r\n"
		return 1
	fi
}

# 查看服务状态
function status(){
    return 0
}

# 重启服务
function restart(){
    return 0
}

case $1 in 
	start )
		start
	;;
	stop)
		stop
	;;
	status)
		status
	;;
	restart)
		restart
	;;
	*)
		start
	;;
esac