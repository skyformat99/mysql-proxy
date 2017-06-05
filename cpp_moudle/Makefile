PHPX_INCLUDE_DIR = "/src/PHP-X"
PHP_INCLUDE = `php-config --includes`
PHP_INCLUDE_DIR = `php-config --include-dir`
PHP_EXTENSION_DIR = `php-config --extension-dir`

all: mysql_proxy.cpp
	c++ -DHAVE_CONFIG_H -g -O2 -o mysql_proxy.so  -fPIC -shared mysql_proxy.cpp  ${PHP_INCLUDE} -std=c++11 -I${PHPX_INCLUDE_DIR}/include -I${PHP_INCLUDE_DIR}/ext/swoole/include -I${PHP_INCLUDE_DIR}/ext/swoole
	#	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so  -fPIC -shared mysql_proxy.cpp  sha1.c ${PHP_INCLUDE} -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}
#	c++ -DHAVE_CONFIG_H -g -o mysql_proxy.so -fPIC -shared mysql_proxy.cpp ${PHP_INCLUDE}  -std=c++0x -I${SWOOLE_SRC}/include -I${SWOOLE_SRC}

install: mysql_proxy.so
	cp mysql_proxy.so ${PHP_EXTENSION_DIR}/
clean: mysql_proxy.so
	rm mysql_proxy.so
