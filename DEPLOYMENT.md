# Purissima Application - Jenkins Deployment Guide

## 🚀 **Ready for Jenkins Deployment!**

Your application is now ready for Jenkins deployment with the following improvements:

## 📁 **New Files Created**

### **Production Files:**

- `Dockerfile.prod` - Production-optimized Dockerfile
- `docker-compose.prod.yml` - Production Docker Compose configuration
- `.dockerignore` - Excludes unnecessary files from Docker build
- `nginx.conf` - Nginx reverse proxy configuration
- `Jenkinsfile` - Complete Jenkins CI/CD pipeline

## 🔧 **Key Improvements Made**

### **1. Production Dockerfile (`Dockerfile.prod`)**

- ✅ **Multi-stage build** for smaller image size
- ✅ **Security**: Non-root user execution
- ✅ **Health checks** built-in
- ✅ **Production optimizations**: No dev dependencies
- ✅ **Proper permissions** and directory structure

### **2. Docker Compose Production (`docker-compose.prod.yml`)**

- ✅ **Environment variables** for configuration
- ✅ **Volume persistence** for storage
- ✅ **Health checks** and restart policies
- ✅ **Optional nginx** reverse proxy
- ✅ **Optional MySQL** database

### **3. Jenkins Pipeline (`Jenkinsfile`)**

- ✅ **Complete CI/CD pipeline**
- ✅ **Docker build and test**
- ✅ **Health checks** before deployment
- ✅ **Automatic cleanup** of old images
- ✅ **Rollback capability**

## 🚀 **Jenkins Setup Instructions**

### **1. Create Jenkins Job**

```bash
# In Jenkins, create a new "Pipeline" job
# Set Pipeline script from SCM
# Repository: Your Git repository
# Script Path: Jenkinsfile
```

### **2. Environment Variables (Optional)**

Set these in Jenkins job configuration:

```bash
APP_URL=http://your-domain.com
DB_HOST=your-db-host
DB_PASSWORD=your-secure-password
```

### **3. Docker Requirements**

Ensure Jenkins has Docker installed and the jenkins user is in the docker group:

```bash
sudo usermod -aG docker jenkins
sudo systemctl restart jenkins
```

## 🐳 **Manual Deployment Commands**

### **Using Docker Compose (Recommended)**

```bash
# Production deployment
docker-compose -f docker-compose.prod.yml up -d

# Check status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f app
```

### **Using Docker directly**

```bash
# Build image
docker build -f Dockerfile.prod -t purissima-app:latest .

# Run container
docker run -d \
  --name purissima-app \
  --restart unless-stopped \
  -p 8000:8000 \
  -v $(pwd)/storage:/var/www/html/storage \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  purissima-app:latest
```

## 🔍 **Health Checks**

### **Application Health**

```bash
# Check if application is running
curl -f http://localhost:8000/

# Check container health
docker ps
docker logs purissima-app
```

### **Jenkins Health Check**

The Jenkins pipeline includes automatic health checks that will:

- ✅ Test the Docker image before deployment
- ✅ Verify the application responds after deployment
- ✅ Check container status

## 📊 **Monitoring & Logs**

### **View Logs**

```bash
# Application logs
docker logs -f purissima-app

# Storage logs
tail -f storage/logs/app.log
```

### **Container Status**

```bash
# Check running containers
docker ps

# Check resource usage
docker stats purissima-app
```

## 🔒 **Security Features**

- ✅ **Non-root user** execution
- ✅ **Security headers** (with nginx)
- ✅ **Rate limiting** (with nginx)
- ✅ **File access restrictions**
- ✅ **Environment variable** configuration

## 🌐 **Access Your Application**

After deployment, your application will be available at:

- **Direct**: `http://your-server:8000`
- **With Nginx**: `http://your-server` (port 80)

## 🆘 **Troubleshooting**

### **Common Issues:**

1. **Port already in use**

   ```bash
   # Check what's using port 8000
   sudo netstat -tlnp | grep :8000
   # Kill the process or use different port
   ```

2. **Permission issues**

   ```bash
   # Fix storage permissions
   sudo chown -R 1000:1000 storage/
   ```

3. **Container won't start**
   ```bash
   # Check logs
   docker logs purissima-app
   # Check if .env file exists
   ls -la .env
   ```

## ✅ **Ready to Deploy!**

Your application is now production-ready for Jenkins deployment. The pipeline will:

1. Build the Docker image
2. Run health checks
3. Deploy to production
4. Verify deployment
5. Clean up old images

**Next Steps:**

1. Push your code to Git repository
2. Create Jenkins job with the provided Jenkinsfile
3. Run the pipeline
4. Access your application at the configured URL
