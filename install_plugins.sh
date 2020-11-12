#!/bin/bash

if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root"
   exit 1
fi

mkdir plugins

(
	cd plugins/

	# WOOCOMMERCE
	wget https://downloads.wordpress.org/plugin/woocommerce.4.5.2.zip
	unzip woocommerce.4.5.2.zip

	# APURATA
	# wget https://github.com/apurata/woocommerce-apurata-payment-gateway/releases/download/v0.1.2/woocommerce-apurata-payment-gateway.zip
	# unzip woocommerce-apurata-payment-gateway.zip
)
