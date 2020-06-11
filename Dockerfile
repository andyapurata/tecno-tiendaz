FROM wordpress
MAINTAINER José Enrique Carrillo Pino <jose@apurata.com>

RUN apt-get update && apt-get install -y --no-install-recommends unzip wget \
    && rm -rf /var/lib/apt/lists/*

USER www-data:www-data

# Install WooCommerce
ENV WOOCOMMERCE_VERSION 4.2.0
RUN wget https://downloads.wordpress.org/plugin/woocommerce.${WOOCOMMERCE_VERSION}.zip -O /tmp/temp.zip \
    && mkdir -p /usr/src/wordpress/wp-content/plugins/ \
    && unzip -q /tmp/temp.zip -d /usr/src/wordpress/wp-content/plugins/ \
    && rm /tmp/temp.zip

# Pre-create folder to prevent permission errors
COPY . /usr/src/wordpress/wp-content/plugins/woocommerce-apurata-payment-gateway

USER 0:0
