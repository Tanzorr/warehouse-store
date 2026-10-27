# currency_rate_api


## Installation
Clone form github
```bash
git clone https://github.com/Tanzorr/currency_rate_api.git

cd currency_rate_api

only if you want to use docker

docker-compose build

docker-compose up -d

docker exec -it currency_rate_api_web_1 bash

composer install

php bin/console doctrine:migrations:migrate

service cron start
```
