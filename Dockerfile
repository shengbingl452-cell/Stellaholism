# 使用内置 Apache 的 PHP 官方镜像
FROM php:8.2-apache

# 1. 安装必要的 PHP 扩展（如果你的 AI 逻辑需要用到 curl 或其他工具）
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

# 2. 启用 Apache 的 rewrite 模块（对很多 PHP 框架和路由很有用）
RUN a2enmod rewrite

# 3. 将你的项目文件复制到 Apache 的默认 Web 目录
COPY . /var/www/html/

# 4. 设置权限，确保 Apache 可以读取文件
RUN chown -R www-data:www-data /var/www/html/

# 5. Render 会注入 PORT 环境变量，Apache 需要监听它
ENV PORT=10000

# 6. 启动前把 Apache 端口改成 $PORT
CMD ["bash", "-c", "sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
