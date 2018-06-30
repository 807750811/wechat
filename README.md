备注：
1、Uploads上传到服务端后，其目录下的权限设置为777
2、需要安装redis和mongodb,以及对应的php驱动
3、需要开启workerman socket服务(php /usr/local/apache2/htdocs/wechat/sockets/start.php start)

优化：
1、mongodb的历史消息分页查询优化
2、接口权限安全优化
	1) RSA加密+HTTPS
	2) https://jwt.io/
	3) AES加密协议：https://blog.catscarlet.com/201701162689.html
3、某些操作完善的事务处理及回滚

待开发：
1、在线好友排位在前 

使用技术：
PHP数据库框架Medoo Version 1.2.1
Slim微框架搭建RESTful风格API Version 2.4.2
php-jwt：JSON Web Tokens（JWT）的编码与解码类库
Socket框架：Workerman Version 3.3.9

