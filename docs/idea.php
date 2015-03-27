# 配置
配置只写一次, 复杂一些, 使用经常用最简洁
配置好可以, 安装, 升级, 使用者完全不用考虑sql
$njorm->install(reinstall=true);
$njorm->upgrade();

支持从数据库直接动态载配置(慢)
也可以导出配置

# procedure, trigger功能
代替复杂难写的trigger, procedure功能 

# validation
像kohana一样, 把验证做在model层, 控制层只要做异常处理
NJException(
'type' => 'duplicate/sqlerror/linkerror/dataerror/.etc'
);

# 默认值
插入时的默认值

# 过滤
入库前的过滤
密码加密
可以用NJExpr转换成Mysql函数