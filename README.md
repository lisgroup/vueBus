H5 自适应苏州实时公交查询系统
===============
## 前后端分离设计
前端代码在 admin 目录，细分为前后台页面；后端代码在 laravel 目录下、

> PHP 的运行环境要求 PHP7.0 以上。
1. PHP >= 7.0
2. PDO PHP Extension
3. MBstring PHP Extension
4. CURL PHP Extension

## 安装方法：
为了方便自己使用，已经将打包好的前端代码放到了 php/public 目录下。即正常部署时候，只需要配置后端 php 环境即可。

### 1. 安装 php 环境 (必须)
```php
git clone https://gitee.com/lisgroup/vueBus.git
cd vueBus/laravel
composer install
cp .env.example .env
```
### 2. 配置项修改 .env 文件数据库
```php
# 修改数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test
DB_USERNAME=root
DB_PASSWORD=root

# 如 redis 可用建议修改
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. 运行安装命令，会自动执行数据迁移和填充，并生成 key
```php
php artisan bus:install
```

### 4. 支持中文全文索引

**两种模式** 

#### 模式一： 开启 elasticsearch 全文搜索
1. elasticsearch 安装方法：

   - 安装请参考： [Ubuntu 安装 elasticsearch 和 analysis-ik 插件](https://note.youdao.com/share/?id=a8fc19ff5dbdf5fcb706957166dba376&type=note#/)

2. 启动 elasticsearch 服务后

3. 在 `.env` 文件中增加配置项；
```bash
SCOUT_DRIVER=elasticsearch
```
4. 生成全文索引；
```php
php artisan elasticsearch:import "App\Models\Line"
```

---
#### 模式二： TNTSearch+jieba-php 实现中文全文搜索

> 注：全文索引存储在 SQLite 中，需 php 开启了以下扩展；
```
  pdo_sqlite
  sqlite3
  mbstring
```

安装方法参见：[TNTSearch+jieba-php 实现中文全文搜索](laravel/readme/5. 2018-12-17-TNTSearch 使用.md)

### 5. 启动 laravels 服务监听 5200 端口(可选：需安装 swoole 扩展)
```php
php artisan laravels start -d
```
更多细节参考：[https://github.com/hhxsv5/laravel-s/blob/master/README-CN.md](https://github.com/hhxsv5/laravel-s/blob/master/README-CN.md)

### 6. 启动定时任务(可选)
```shell
# 使用 crontab 的定时任务调用 php artisan 调度任务：
crontab -e

# 追加如下内容： 

* * * * * php /home/ubuntu/vueBus/laravel/artisan schedule:run >> /dev/null 2>&1

# 最后 ctrl + o 保存退出即可。
```

### 7. 启动队列(可选)
```shell
php artisan queue:work
```

### 8. 可选，安装 npm 扩展
```node
# 切换到上级 app 目录下
cd ../admin
npm i
# 本地测试
npm run dev

# 打包(可选)
npm run build
# 将 dist 目录下的文件 copy 到 php/public 目录。
```
~~# 直接打包到 laravel/public 目录(注意备份 /laravel/public/index.php 文件)~~
~~npm run build:pro~~

## 域名绑定
域名需要绑定到根目录，即项目的 laravel/public 目录下。

### 1. Nginx 配置示例： (未启动 laravel-s 的扩展)
```shell
server {
    listen 443;
    root /www/vueBus/laravel/public;
    server_name www.guke1.com; # 改为绑定证书的域名
    
    # ssl 配置
    ssl on;
    ssl_certificate /etc/bundle.crt; # 改为自己申请得到的 crt 文件的名称
    ssl_certificate_key /etc/my.key; # 改为自己申请得到的 key 文件的名称
    ssl_session_timeout 5m;
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:HIGH:!aNULL:!MD5:!RC4:!DHE;
    ssl_prefer_server_ciphers on;

    # 文件不存在 转发 index.php 处理
    location / {
        #index index.php;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ (\.php)$ {
        fastcgi_pass  unix:/tmp/php-cgi.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PHP_ADMIN_VALUE "open_basedir=$document_root/../:/tmp/:/proc/";
        include        fastcgi_params;
        fastcgi_param  PATH_INFO $fastcgi_script_name;
    }

    location ~ .*\.(css|img|js|gif|jpg|jpeg|png|bmp|swf)$
    {
        # root $root_path; 
        expires     30d;
    }

    location ~ /.well-known
    {
    	allow all;
    }

    location ~ /\.
    {
    	deny all;
    }

    access_log /home/wwwlogs/laravel.log
    # error_log /home/wwwlogs/laravel_error.log;
}
```
### 2. 启动 laravels 的 Nginx 示例配置：
```php
#gzip on;
#gzip_min_length 1024;
#gzip_comp_level 2;
#gzip_types text/plain text/css text/javascript application/json application/javascript application/x-javascript application/xml application/x-httpd-php image/jpeg image/gif image/png font/ttf font/otf image/svg+xml;
#gzip_vary on;
#gzip_disable "msie6";
upstream laravels {
    # By IP:Port
    server 127.0.0.1:5200 weight=5 max_fails=3 fail_timeout=30s;
    # By UnixSocket Stream file
    #server unix:/xxxpath/laravel-s-test/storage/laravels.sock weight=5 max_fails=3 fail_timeout=30s;
    #server 192.168.1.1:5200 weight=3 max_fails=3 fail_timeout=30s;
    #server 192.168.1.2:5200 backup;
}
server {
    listen 80;
    # 别忘了绑Host哟
    server_name www.bus.com;
    root /home/www/vueBus/laravel/public;
    access_log /home/wwwlogs/nginx/$server_name.access.log;
    autoindex off;
    index index.html index.htm;
    # Nginx处理静态资源(建议开启gzip)，LaravelS处理动态资源。
    location / {
        try_files $uri $uri/index.html @laravels;
    }
    # 当请求PHP文件时直接响应404，防止暴露public/*.php
    #location ~* \.php$ {
    #    return 404;
    #}
    location @laravels {
        proxy_http_version 1.1;
        # proxy_connect_timeout 60s;
        # proxy_send_timeout 60s;
        # proxy_read_timeout 120s;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Real-PORT $remote_port;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header Server-Protocol $server_protocol;
        proxy_set_header Server-Name $server_name;
        proxy_set_header Server-Addr $server_addr;
        proxy_set_header Server-Port $server_port;
        proxy_pass http://laravels;
    }
}
```

## 使用方法
浏览器访问： https://www.guke1.com ，可以查看

在输入框输入查询的公交车，（如：快1）点击搜索后，会出现搜索到的车次，再次点击需要查询车次的方向，即可查看实时公交状态。

