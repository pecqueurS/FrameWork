FRAMEWORK - Stephane PECQUEUR
HYLACORE
==========

V1.0.1

DESCRIPTION

FRAMEWORK PERSO orienté Objet


Host :

	<VirtualHost *:80>

		ServerAdmin [EMAIL]
		ServerName [URL]
		Alias /bootstrap "/home/[USER]/[FRAMEWORK]/www"

		DocumentRoot /home/[USER]/[FRAMEWORK]/App/[APP_NAME]/www

		<Directory /home/[USER]/[FRAMEWORK]/App/[APP_NAME]/www >
			AllowOverride none
			Options -Indexes FollowSymLinks MultiViews
			Order deny,allow
			Deny from all
			Allow from 127.0.0.1
			Allow from 192.168.0.
			Allow from 192.168.1.

			RewriteEngine On
			RewriteCond %{REQUEST_FILENAME} -s [OR]
			RewriteCond %{REQUEST_FILENAME} -l
			RewriteRule ^.*$ - [NC,L]

			RewriteCond %{REQUEST_FILENAME} -d
			RewriteCond %{REQUEST_FILENAME} -s
			RewriteRule ^.*$ - [NC,L]

			RewriteRule ^.*$ /bootstrap/index.php [NC,PT]

		</Directory>
		ServerSignature Off

	</VirtualHost>



URL Rewrite :

	Creer un .htaccess à la racine de "www" d'apres le fichier "htaccess" à la racine du site.



* Le dossier du projet doit être déposé dans un endroit accessible pour Apache pour pouvoir executer les fichiers php.


