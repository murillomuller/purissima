# 🚀 Unraid Jenkins Setup for Purissima

## Quick Setup Guide for Existing Unraid Jenkins Server

Since you already have Jenkins running on Unraid, here's the simple setup:

## 📋 **Prerequisites Check**

Make sure your Unraid Jenkins has:

- ✅ Docker installed and accessible
- ✅ Git plugin installed
- ✅ Jenkins user in docker group

## 🔧 **Jenkins Job Setup**

### **1. Create New Pipeline Job**

1. Go to Jenkins → New Item
2. Enter name: `purissima-app`
3. Select: **Pipeline**
4. Click OK

### **2. Configure Pipeline**

1. **Pipeline Definition**: Pipeline script from SCM
2. **SCM**: Git
3. **Repository URL**: Your Git repository URL
4. **Branch**: `*/main` (or your main branch)
5. **Script Path**: `Jenkinsfile`

### **3. Environment Variables (Optional)**

In Jenkins job configuration, add these if needed:

```
APP_URL=http://your-unraid-ip:8000
```

## 🐳 **Docker Setup on Unraid**

### **No Manual Setup Required!**

Docker will automatically create and manage the volumes:

- `purissima-storage` - for application data
- `purissima-logs` - for application logs

The volumes will be created automatically when the container starts.

## 🚀 **Deployment Process**

### **Automatic (Recommended)**

1. Push code to your Git repository
2. Jenkins will automatically:
   - Pull latest code
   - Build Docker image
   - Deploy container
   - Run health checks

### **Manual Trigger**

1. Go to Jenkins job
2. Click "Build Now"
3. Monitor the build progress

## 📊 **Access Your Application**

After successful deployment:

- **Application**: `http://your-unraid-ip:8000`
- **Unraid Docker**: Container will appear in Unraid Docker tab
- **Logs**: Available in Jenkins build logs

## 🔍 **Monitoring**

### **Jenkins Build Logs**

- Check build status in Jenkins
- View console output for any errors

### **Container Status**

```bash
# SSH into Unraid
docker ps | grep purissima-app
docker logs purissima-app
```

### **Application Health**

```bash
# Test application
curl http://localhost:8000/
```

## 🛠️ **Troubleshooting**

### **Common Issues:**

1. **Permission Denied**

   ```bash
   # Fix permissions
   sudo chown -R jenkins:jenkins /mnt/user/appdata/purissima
   ```

2. **Docker Not Found**

   ```bash
   # Add jenkins to docker group
   sudo usermod -aG docker jenkins
   sudo systemctl restart jenkins
   ```

3. **Port Already in Use**

   ```bash
   # Check what's using port 8000
   sudo netstat -tlnp | grep :8000
   # Kill process or change port in Jenkinsfile
   ```

4. **Build Fails**
   - Check Jenkins console output
   - Verify Git repository access
   - Ensure Dockerfile.prod exists

## 📁 **Docker Volumes Created**

Docker will automatically create these named volumes:

```
purissima-storage    # Application data (logs, uploads, output, pdf)
purissima-logs       # Application logs
```

You can view them with:

```bash
docker volume ls | grep purissima
```

## 🔄 **Update Process**

To update your application:

1. Push changes to Git
2. Jenkins automatically builds and deploys
3. Old container is stopped and removed
4. New container starts with latest code

## ✅ **Success Indicators**

You'll know it's working when:

- ✅ Jenkins build shows "SUCCESS"
- ✅ Container appears in Unraid Docker tab
- ✅ Application responds at `http://your-unraid-ip:8000`
- ✅ Health check passes

## 🆘 **Need Help?**

If you encounter issues:

1. Check Jenkins build logs
2. Verify Docker is running: `docker ps`
3. Check container logs: `docker logs purissima-app`
4. Test application: `curl http://localhost:8000/`

---

**That's it! Your Purissima app will now automatically deploy whenever you push code to Git! 🎉**
