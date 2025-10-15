# 🚀 Jenkins Freestyle Setup for Purissima

## Simple Setup Guide for Jenkins Freestyle Job

This is much simpler than pipeline - just a shell script!

## 📋 **Prerequisites**

Make sure your Unraid Jenkins has:

- ✅ Docker installed and accessible
- ✅ Git plugin installed
- ✅ Jenkins user in docker group

## 🔧 **Jenkins Freestyle Job Setup**

### **1. Create New Freestyle Job**

1. Go to Jenkins → **New Item**
2. Enter name: `purissima-app`
3. Select: **Freestyle project**
4. Click **OK**

### **2. Configure Source Code Management**

1. **Source Code Management**: Git
2. **Repository URL**: Your Git repository URL
3. **Branch**: `*/main` (or your main branch)
4. **Credentials**: Add if your repo is private

### **3. Configure Build Steps**

#### **Build Step:**

1. **Build Steps** → **Add build step** → **Execute shell**
2. **Command**:
   ```bash
   chmod +x build.sh
   ./build.sh
   ```

#### **Post-Build Step:**

1. **Post-build Actions** → **Add post-build action** → **Execute shell**
2. **Command**:
   ```bash
   chmod +x post-build.sh
   ./post-build.sh
   ```

### **4. Configure Build Triggers (Optional)**

- **GitHub hook trigger for GITScm polling** (if using GitHub)
- **Poll SCM** with schedule like `H/5 * * * *` (every 5 minutes)

### **5. Configure Additional Post-build Actions (Optional)**

- **Archive the artifacts** (if you want to keep build artifacts)
- **Publish build status to Git** (if using GitHub)
- **Email notification** (if you want email alerts)

## 🚀 **Deployment Process**

### **Automatic (if triggers configured)**

1. Push code to Git repository
2. Jenkins automatically detects changes
3. Runs the `deploy.sh` script
4. Application deploys automatically

### **Manual**

1. Go to Jenkins job
2. Click **"Build Now"**
3. Monitor the build progress in console output

## 📊 **What the Scripts Do**

### **Build Script (`build.sh`):**

1. **Build Docker image** using `Dockerfile.prod`
2. **Tag image** as latest
3. **Show image info**
4. **Save build info** for post-build step

### **Post-Build Script (`post-build.sh`):**

1. **Load build info** from build step
2. **Stop existing container** (if running)
3. **Remove old container** (if exists)
4. **Start new container** on port 5666 with Docker volumes
5. **Wait for container** to be ready
6. **Run health check** to verify deployment
7. **Show container status** and volumes
8. **Clean up old images** (keep last 5)
9. **Display success message** with useful commands

## 🔍 **Monitoring**

### **Jenkins Build Logs**

- Check build status in Jenkins
- View console output for detailed logs
- Look for colored status messages

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
   # Make script executable
   chmod +x deploy.sh
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
   # Kill process or change port in deploy.sh
   ```

4. **Build Fails**
   - Check Jenkins console output
   - Verify Git repository access
   - Ensure Dockerfile.prod exists

## 📁 **Docker Volumes Created**

The script automatically creates these named volumes:

- `purissima-storage` - Application data
- `purissima-logs` - Application logs

View them with:

```bash
docker volume ls | grep purissima
```

## 🔄 **Update Process**

To update your application:

1. Push changes to Git
2. Jenkins automatically builds and deploys (if triggers configured)
3. Or manually trigger build in Jenkins
4. Old container is stopped and removed
5. New container starts with latest code

## ✅ **Success Indicators**

You'll know it's working when:

- ✅ Jenkins build shows "SUCCESS"
- ✅ Console shows "🎉 Deployment completed successfully!"
- ✅ Container appears in Unraid Docker tab
- ✅ Application responds at `http://your-unraid-ip:5666`
- ✅ Health check passes

## 🆘 **Need Help?**

If you encounter issues:

1. Check Jenkins build logs
2. Verify Docker is running: `docker ps`
3. Check container logs: `docker logs purissima-app`
4. Test application: `curl http://localhost:5666/`

---

**That's it! Much simpler than pipeline - just a shell script! 🎉**
