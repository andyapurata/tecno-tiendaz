FROM wordpress

RUN apt-get update && apt-get install -y --no-install-recommends unzip wget

# Install WooCommerce
ENV WOOCOMMERCE_VERSION 4.2.0
RUN wget https://downloads.wordpress.org/plugin/woocommerce.${WOOCOMMERCE_VERSION}.zip -O /tmp/temp.zip \
    && mkdir -p /usr/src/wordpress/wp-content/plugins/ \
    && cd /usr/src/wordpress/wp-content/plugins/ \
    && unzip /tmp/temp.zip \
    && rm /tmp/temp.zip

# Pre-create folder to prevent permission errors
RUN mkdir -p /usr/src/wordpress/wp-content/plugins/woocommerce-apurata-payment-gateway && \
	chown www-data:www-data /usr/src/wordpress/wp-content/plugins/woocommerce-apurata-payment-gateway
