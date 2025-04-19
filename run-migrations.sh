#!/bin/bash

# 项目目录
PROJECT_DIR="/opt/1panel/apps/openresty/openresty/www/sites/test.toiroworld.com/index"

# 切换到项目目录
cd "$PROJECT_DIR"

# 运行迁移脚本
php run-migrations.php 