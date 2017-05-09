#SWOOLE_SRC = "/src/xinhua"
SWOOLE_SRC = "/root/swoole-src"
PHP_INCLUDE = `/xinhua/bin/php-config --includes`
PHP_LIBS = `/xinhua/bin/php-config --libs`
PHP_LDFLAGS = `/xinhua/bin/php-config --ldflags`

all: mysql_proxy.cpp
	c++ -DHAVE_CONFIG_H -g -O2 -o mysql_proxy.so  -fPIC -shared mysql_proxy.cpp  ${PHP_INCLUDE} -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
	#	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so  -fPIC -shared mysql_proxy.cpp  sha1.c ${PHP_INCLUDE} -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
#	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so -fPIC -shared mysql_proxy.cpp ${PHP_INCLUDE}  -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
clean: mysql_proxy.so
	rm mysql_proxy.so
