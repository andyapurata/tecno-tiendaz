#!/bin/bash

# Get version
VERSION=`git tag --points-at HEAD`
[[ -z "$VERSION" ]] && { echo "You must set git tag with version, e.g. 0.0.1"; exit 1; }

# Copy files
mkdir -p woocommerce-apurata-payment-gateway/
cp woocommerce-apurata-payment-gateway.php woocommerce-apurata-payment-gateway/
cp LICENSE woocommerce-apurata-payment-gateway/
cp readme-wc.txt woocommerce-apurata-payment-gateway/readme.txt

# Change version in php file
sed -i "3s/.*/ * Version:           $VERSION/" woocommerce-apurata-payment-gateway/woocommerce-apurata-payment-gateway.php

zip -r "v${VERSION}.zip" woocommerce-apurata-payment-gateway/
