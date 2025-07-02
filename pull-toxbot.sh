#!/bin/bash

# Define variables
CONTAINER_NAME="toxbot"
IMAGE_NAME="toxbot"
LOG_FILE="toxbot.log"

# Pull latest changes
echo "Pulling latest changes from GitHub..." | tee -a "$LOG_FILE"
git reset --hard
git pull origin main >> "$LOG_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo "Git pull successful. Rebuilding Docker container..." | tee -a "$LOG_FILE"
    
    # Stop and remove existing container
    docker stop $CONTAINER_NAME
    docker rm $CONTAINER_NAME
    
    # Build new image
    docker build -t $IMAGE_NAME .
    
    # Run new container
    docker run -d \
        --name $CONTAINER_NAME \
        --restart unless-stopped \
        -v /docker_volumes/rustdesk/hbbs:/data/rustdesk:ro \
        $IMAGE_NAME

    echo "Docker container rebuilt and started." | tee -a "$LOG_FILE"
else
    echo "Git pull failed. No changes applied." | tee -a "$LOG_FILE"
fi

echo "Script execution completed." | tee -a "$LOG_FILE"
