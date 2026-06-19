#!/bin/bash
# Khởi động backend với limit upload lớn cho video
php -d upload_max_filesize=512M -d post_max_size=512M artisan serve --host=0.0.0.0 --port=8000
