#mysql-proxy
简介：mysqlproxy 通过c扩展实现了mysql协议，应用层逻辑用php+swoole编写，业务代码只需要将配置文件的ip和端口改成proxy的ip和端口即可。

## 特性列表

* MySQL连接池
* 自动读写分离
* 从库负载均衡（加权轮训算法）
* 慢SQL/超大结果集监控和报警
* 自动分库分表