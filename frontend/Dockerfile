# Frontend static file server.
# Serves HTML, CSS, JS, and assets via Nginx on port 80.
# This container is placed behind the Nginx gateway reverse proxy.

FROM nginx:alpine

# Use custom Nginx config (server_tokens off, etc.)
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy static assets into the Nginx web root
COPY index.html   /usr/share/nginx/html/
COPY styles.css   /usr/share/nginx/html/
COPY src/          /usr/share/nginx/html/src/
COPY assets/       /usr/share/nginx/html/assets/

EXPOSE 80
