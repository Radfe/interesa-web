$env:Path = "C:\php;$env:Path"
Set-Location "C:\data\praca\webova_stranka\github"
php -S 127.0.0.1:5000 -t public public/router.php
