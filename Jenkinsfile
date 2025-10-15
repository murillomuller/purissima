pipeline {
    agent any
    
    environment {
        DOCKER_IMAGE = 'purissima-app'
        DOCKER_TAG = "${BUILD_NUMBER}"
        CONTAINER_NAME = 'purissima-app'
        // Jenkins workspace path
        WORKSPACE_PATH = "${WORKSPACE}"
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Build Docker Image') {
            steps {
                script {
                    // Build production Docker image
                    sh "docker build -f Dockerfile.prod -t ${DOCKER_IMAGE}:${DOCKER_TAG} ."
                    sh "docker tag ${DOCKER_IMAGE}:${DOCKER_TAG} ${DOCKER_IMAGE}:latest"
                    
                    // Show image info
                    sh "docker images ${DOCKER_IMAGE}"
                }
            }
        }
        
        stage('Test') {
            steps {
                script {
                    // Run basic health check
                    sh """
                        docker run --rm -d --name test-container -p 8001:8000 ${DOCKER_IMAGE}:${DOCKER_TAG}
                        sleep 10
                        curl -f http://localhost:8001/ || exit 1
                        docker stop test-container
                    """
                }
            }
        }
        
        stage('Deploy') {
            steps {
                script {
                    // Stop existing container
                    sh "docker stop ${CONTAINER_NAME} || true"
                    sh "docker rm ${CONTAINER_NAME} || true"
                    
                    // Run new container with Docker named volumes
                    sh """
                        docker run -d \
                            --name ${CONTAINER_NAME} \
                            --restart unless-stopped \
                            -p 8000:8000 \
                            -v purissima-storage:/var/www/html/storage \
                            -v purissima-logs:/var/log/purissima \
                            -e APP_ENV=production \
                            -e APP_DEBUG=false \
                            -e TZ=America/Sao_Paulo \
                            --label "net.unraid.docker.managed=dockerman" \
                            --label "net.unraid.docker.webui=http://[IP]:[PORT:8000]/" \
                            ${DOCKER_IMAGE}:${DOCKER_TAG}
                    """
                    
                    // Show container info
                    sh "docker ps | grep ${CONTAINER_NAME}"
                    
                    // Show volumes
                    sh "docker volume ls | grep purissima"
                }
            }
        }
        
        stage('Health Check') {
            steps {
                script {
                    // Wait for container to start
                    sh "sleep 15"
                    
                    // Perform health check
                    sh "curl -f http://localhost:8000/ || exit 1"
                    
                    // Check container status
                    sh "docker ps | grep ${CONTAINER_NAME}"
                }
            }
        }
        
        stage('Cleanup') {
            steps {
                script {
                    // Remove old images (keep last 5)
                    sh """
                        docker images ${DOCKER_IMAGE} --format "table {{.Tag}}\t{{.ID}}" | \
                        tail -n +2 | \
                        head -n -5 | \
                        awk '{print \$2}' | \
                        xargs -r docker rmi || true
                    """
                }
            }
        }
    }
    
    post {
        always {
            // Clean up test containers
            sh "docker stop test-container || true"
            sh "docker rm test-container || true"
        }
        
        success {
            echo "Deployment successful! Application is running at http://localhost:8000"
        }
        
        failure {
            echo "Deployment failed! Check the logs above."
        }
    }
}
