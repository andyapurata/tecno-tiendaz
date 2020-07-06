#!/bin/bash

# Assume you are in the project directory

docker-compose build;

# We use cp instead of volumes for a good reason. See readme-dev.md
docker cp . $(docker-compose ps -q wordpress):/var/www/html/wp-content/plugins/woocommerce-apurata-payment-gateway

docker-compose up -d;
