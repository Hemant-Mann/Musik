# Musik
A site for Music Lovers


## Steps for installation
Follow these steps to configure the repo
```bash
git clone https://github.com/Hemant-Mann/Musik
cd Musik
chmod +x init && ./init
```

Also add virtual host configuration to apache
```bash
<VirtualHost *:80>
    ServerAdmin admin@musik.io
    DocumentRoot "/path/to/cloned/repo/"
    ServerName musik.io
    ServerAlias www.musik.io
</VirtualHost>
```