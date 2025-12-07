
sudo tee /home/serpoai/conf/web/ai.serpocoin.io/nginx.conf > /dev/null << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name ai.serpocoin.io www.ai.serpocoin.io;

    root /var/www/serpoai/public;
    index index.php index.html;

    access_log /var/log/nginx/domains/ai.serpocoin.io.log combined;
    access_log /var/log/nginx/domains/ai.serpocoin.io.bytes bytes;
    error_log /var/log/nginx/domains/ai.serpocoin.io.error.log error;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include /etc/nginx/fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    include /home/serpoai/conf/web/ai.serpocoin.io/nginx.conf_*;
}
EOF
