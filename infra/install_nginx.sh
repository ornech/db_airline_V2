#!/bin/sh

# =================================================================
# SCRIPT D'INSTALLATION SERVEUR WEB LÉGER (ALPINE + NGINX + PHP-FPM)
# =================================================================

echo "--- Mise à jour des dépôts ---"
apk update

echo "--- Installation de Nginx et PHP 8.3 avec PDO ---"
# On installe le serveur web, PHP-FPM et les extensions nécessaires
apk add nginx php83 php83-fpm php83-pdo php83-pdo_mysql php83-mbstring php83-openssl

echo "--- Configuration de PHP-FPM ---"
# Par défaut, PHP-FPM écoute sur 127.0.0.1:9000 sur Alpine
# On s'assure que le répertoire de travail existe
mkdir -p /var/www/html
chown -R nginx:nginx /var/www/html

echo "--- Configuration de Nginx ---"
# Création d'un fichier de configuration minimal pour Nginx
cat <<EOF > /etc/nginx/http.d/default.conf
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ =404;
    }

    # Passage des scripts PHP à PHP-FPM
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
EOF

echo "--- Démarrage des services ---"
# On active les services pour qu'ils se lancent au boot
rc-update add nginx default
rc-update add php-fpm83 default

# On lance les services immédiatement
rc-service nginx restart
rc-service php-fpm83 restart

echo "--- Création d'un fichier de test (info.php) ---"
echo "<?php phpinfo(); ?>" > /var/www/html/info.php

echo "----------------------------------------------------"
echo "Installation terminée !"
echo "Accédez à http://$(hostname -i)/info.php pour vérifier."
echo "----------------------------------------------------"
