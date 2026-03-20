# Nginx gateway reverse proxy.
# Routes traffic between the frontend and backend containers:
#  - Port 80:   API requests → PHP-FPM backend (FastCGI)
#  - Port 8080: Static assets → Frontend Nginx container

FROM nginx:alpine

# Replace default config with our gateway config
RUN rm /etc/nginx/conf.d/default.conf
COPY default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80 8080
