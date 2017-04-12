#SWOOLE_SRC = "/src/xinhua"
SWOOLE_SRC = "/src/swoole-src"
PHP_INCLUDE = `php-config --includes`
PHP_LIBS = `php-config --libs`
PHP_LDFLAGS = `php-config --ldflags`

all: mysql_proxy.cpp
	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so  -fPIC -shared mysql_proxy.cpp  ${PHP_INCLUDE} -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
	#	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so  -fPIC -shared mysql_proxy.cpp  sha1.c ${PHP_INCLUDE} -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
#	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so -fPIC -shared mysql_proxy.cpp ${PHP_INCLUDE}  -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
clean: mysql_proxy.so
	rm mysql_proxy.so
