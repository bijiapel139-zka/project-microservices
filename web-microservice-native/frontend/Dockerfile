# ==========================================
# Dockerfile Frontend
# Isinya cuma berkas statis (HTML, CSS, JS), jadi cukup web server ringan nginx.
# ==========================================
FROM nginx:alpine

# Salin semua berkas frontend ke folder yang disajikan nginx
COPY . /usr/share/nginx/html

EXPOSE 80
