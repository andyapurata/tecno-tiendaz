# Run in prd

Just run:

	git pull
	docker-compose build
	docker-compose up -d
	# Go to: http://woocommerce-demo-1515033668.us-west-2.elb.amazonaws.com/

# Run in dev

	# Run the following just the first time, see "PROBLEM WITH VOLUMES"
	docker-compose build
	docker-compose up

	# Then stop the script, and from now on, run:
	./run_dev.sh


# PROBLEM WITH VOLUMES

Lo que sucede es /var/www/data es un volúmen. Y la primera vez que se corre el
container, todo wordpress se copia a este volúmen. El problema es que al montar otro volúmen dentro
de este volúmen (para tener el plugin disponible como volúmen), las carpetas padre se crean con
owner=root. Esto pasa porque el volúmen /var/www/data está vacío la primera vez que se ejecuta. Por
eso, la primera ejecución debe ser sin el volúmen del plugin y las subsiguientes ya pueden ser sin
eso.

En el caso de un deploy, no podemos tener el volúmen definido en el yml de producción, puesto
que tendríamos el problema de los permisos, así que lo que hacemos es que cada vez que se hace un
deployment, se copia nuevamente la carpeta a dentro del volúmen /var/www/html.

En caso de tener problemas. Puedes correr el siguiente comando para resetear los volúmenes:

	docker-compose down -v

