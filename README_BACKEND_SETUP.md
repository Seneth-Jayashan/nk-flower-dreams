NK Flower Dreams PHP Backend Setup

1. Requirements
- PHP 8.1+
- MySQL 8+
- Apache or Nginx with PHP enabled

2. Database
- Create database and tables by running database.sql

3. Configure DB credentials
- Edit backend/config.php
- Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

4. Run website
- Serve project root with PHP-enabled web server
- Public site: /index.php
- Admin setup: /admin/setup.php (first time only)
- Admin login: /admin/login.php

5. Security notes
- Delete or restrict /admin/setup.php after first admin creation
- Use HTTPS in production so secure session cookies are enforced
- Keep backend/.htaccess and images/uploads/.htaccess enabled

6. Product management
- Login to admin dashboard
- Add, edit, hide/show, and delete products
- Uploaded images are stored in images/uploads
- Public homepage collection auto-loads active products from SQL
