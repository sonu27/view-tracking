## Build and Deploy
```
docker build -t thedotsteam/view-tracking:php -f Dockerfile-php .
docker push thedotsteam/view-tracking:php

docker build -t thedotsteam/view-tracking:nginx ./docker/nginx
docker push thedotsteam/view-tracking:nginx
```
