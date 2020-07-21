#!/bin/bash

# You must set up the following config in ~/.ssh/config:
# Host woocommerce_demo
# 	HostName ec2-34-217-90-133.us-west-2.compute.amazonaws.com
# 	User ubuntu
# 	IdentityFile ~/.ssh/apurata.pem

ssh woocommerce_demo "(cd woocommerce-apurata-payment-gateway; git pull;)"
ssh woocommerce_demo "(cd woocommerce-apurata-payment-gateway; ./deploy_scripts/run_on_server.sh;)"
