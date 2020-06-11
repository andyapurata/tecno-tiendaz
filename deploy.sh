#!/bin/bash

# You must set up this config in ~/.ssh/config
ssh woocommerce_demo << EOF
	(
		cd woocommerce-apurata-payment-gateway;
		git pull;
		docker-compose build;

		# We use cp instead of volumes for a good reason. See readme-dev.md
		docker cp . $(docker-compose ps -q wordpress):/var/www/html/wp-content/plugins/woocommerce-apurata-payment-gateway

		docker-compose up -d;
	)
EOF
