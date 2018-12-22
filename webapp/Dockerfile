# Dockerfile to build webapp image

# Base image
FROM nginx:1.15.0

# Copy nginx configuration file
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy content from build folder to nginx folder
COPY ./build/ /usr/share/nginx/html