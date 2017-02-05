#用户表
DROP table IF exists wh_user;
CREATE table wh_user(
	id tinyint unsigned not null auto_increment comment "索引",
	username varchar(30) not null comment "用户名",
	password char(32) not null comment "密码",
	primary key(id)
) engine=MyISAM default charset=utf8 comment "用户表";

