# Docker Setup Guide for ZStyle

This guide will help you run the ZStyle e-commerce application using Docker containers.

## Architecture

The Docker setup consists of three main services:

1. **Database** (MySQL 8.0) - Runs on port 3307
2. **Web Server** (PHP 8.1 + Apache) - Runs on port 8080
3. **phpMyAdmin** (Database Management) - Runs on port 8081

## Prerequisites

- Docker Desktop installed on your system
- Docker Compose (included with Docker Desktop)
- At least 4GB of available RAM
- Ports 8080, 8081, and 3307 available

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/Plinh-Ctuyen-QHung-VHau/Zstyle.git
cd Zstyle
```

### 2. Start Docker Containers

```bash
docker-compose up -d
```

This command will:

- Build the web server image
- Download MySQL and phpMyAdmin images
- Create and start all containers
- Import the database automatically

### 3. Wait for Services to Start

The first time you run this, it may take a few minutes to download images and set up the database.

Check the status of containers:

```bash
docker-compose ps
```

### 4. Access the Application

- **Main Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Database**: localhost:3307

### 5. Database Credentials

**For Application (Docker):**

- Host: `database`
- Database: `zstyle`
- Username: `zstyle_user`
- Password: `zstyle_password`

**For phpMyAdmin:**

- Server: `database`
- Username: `root`
- Password: `root_password`

OR

- Username: `zstyle_user`
- Password: `zstyle_password`

## Docker Commands

### Start Containers

```bash
docker-compose up -d
```

### Stop Containers

```bash
docker-compose stop
```

### Stop and Remove Containers

```bash
docker-compose down
```

### Stop and Remove Containers + Volumes (Delete Database Data)

```bash
docker-compose down -v
```

### View Logs

```bash
# All services
docker-compose logs

# Specific service
docker-compose logs webserver
docker-compose logs database
docker-compose logs phpmyadmin

# Follow logs in real-time
docker-compose logs -f webserver
```

### Restart Containers

```bash
docker-compose restart
```

### Rebuild Web Server

```bash
docker-compose up -d --build webserver
```

### Execute Commands in Container

```bash
# Access bash in web server
docker-compose exec webserver bash

# Access MySQL CLI
docker-compose exec database mysql -u zstyle_user -p zstyle
```

## Database Configuration

### Using Docker Configuration

When running in Docker, you need to update the database connection file:

**Option 1: Rename the Docker config file (Recommended)**

```bash
# Windows PowerShell
Rename-Item model\connectdb.php model\connectdb.local.php
Rename-Item model\connectdb.docker.php model\connectdb.php

# Linux/Mac
mv model/connectdb.php model/connectdb.local.php
mv model/connectdb.docker.php model/connectdb.php
```

**Option 2: Manually update `model/connectdb.php`**

Replace the connection details:

```php
function pdo_get_connection(){
   $dburl = "mysql:host=database;dbname=zstyle;charset=utf8";
   $username = 'zstyle_user';
   $password = 'zstyle_password';
   // ...
}
```

## File Permissions

The Dockerfile automatically sets proper permissions for:

- `/var/www/html/upload` - For uploaded product images
- `/var/www/html/view/layout/assets/images` - For static images

If you encounter permission issues:

```bash
docker-compose exec webserver chmod -R 777 /var/www/html/upload
docker-compose exec webserver chmod -R 777 /var/www/html/view/layout/assets/images
```

## Troubleshooting

### Issue: Containers won't start

**Solution**: Check if ports are already in use

```bash
# Windows
netstat -ano | findstr :8080
netstat -ano | findstr :8081
netstat -ano | findstr :3307

# Linux/Mac
lsof -i :8080
lsof -i :8081
lsof -i :3307
```

If ports are in use, either stop the conflicting services or change ports in `docker-compose.yml`.

### Issue: Database connection error

**Solution 1**: Wait for database to be fully ready

```bash
docker-compose logs database
```

Look for "ready for connections" message.

**Solution 2**: Restart the web server after database is ready

```bash
docker-compose restart webserver
```

### Issue: Can't see uploaded images

**Solution**: Check volume mounts and permissions

```bash
docker-compose exec webserver ls -la /var/www/html/upload
docker-compose exec webserver chmod -R 777 /var/www/html/upload
```

### Issue: Changes to code not reflecting

**Solution**: The code directory is mounted as a volume, so changes should be immediate. Try:

```bash
docker-compose restart webserver
```

### Issue: Need to reset database

**Solution**: Remove volume and restart

```bash
docker-compose down -v
docker-compose up -d
```

## Production Considerations

For production deployment:

1. **Change default passwords** in `docker-compose.yml`
2. **Use environment variables** for sensitive data
3. **Add SSL/TLS** certificates (use nginx proxy)
4. **Enable log rotation**
5. **Set up automated backups** for database
6. **Use specific image versions** instead of `latest`
7. **Remove phpMyAdmin** service for security

Example production changes:

```yaml
# Use environment file
environment:
  - DB_HOST=${DB_HOST}
  - DB_NAME=${DB_NAME}
  - DB_USER=${DB_USER}
  - DB_PASSWORD=${DB_PASSWORD}
```

Create `.env` file:

```
DB_HOST=database
DB_NAME=zstyle
DB_USER=zstyle_user
DB_PASSWORD=your_secure_password_here
```

## Backup and Restore

### Backup Database

```bash
docker-compose exec database mysqldump -u zstyle_user -pzstyle_password zstyle > backup.sql
```

### Restore Database

```bash
docker-compose exec -T database mysql -u zstyle_user -pzstyle_password zstyle < backup.sql
```

## Stopping the Application

When you're done working:

```bash
# Stop containers (data persists)
docker-compose stop

# Stop and remove containers (data persists)
docker-compose down

# Stop, remove containers and delete all data
docker-compose down -v
```

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [MySQL Docker Image](https://hub.docker.com/_/mysql)
- [PHP Docker Image](https://hub.docker.com/_/php)

## Support

If you encounter any issues:

1. Check the logs: `docker-compose logs`
2. Verify all containers are running: `docker-compose ps`
3. Check the main README.md for application-specific issues
4. Create an issue on GitHub

---

**Happy Coding with Docker!** 🐳
