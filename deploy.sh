#!/bin/bash

# You must set up this config in ~/.ssh/config
ssh woocommerce_demo "(cd woocommerce-apurata-payment-gateway; git pull;)"
ssh woocommerce_demo "(cd woocommerce-apurata-payment-gateway; ./deploy_scripts/run_on_server.sh;)"
