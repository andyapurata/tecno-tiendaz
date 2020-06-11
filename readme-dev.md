# Run in prd

Just run:

	docker-compose build
	docker-compose up

# Run in dev

	# Run the following just the first time
	docker-compose build
	docker-compose up

	# Then stop the script, and from now on, run:
	./run_dev.sh

*Explicación:* Lo que sucede es /var/www/data es un volúmen. Y la primera vez que se corre el
container, todo wordpress se copia a este volúmen. El problema es que al montar un volúmen dentro
de este volúmen (para tener el plugin disponible como volúmen), las carpetas padre se crean con
owner=root. Esto pasa porque el volúmen /var/www/data está vacío la primera vez que se ejecuta. Por
eso, la primera ejecución debe ser sin el volúmen del plugin y las subsiguientes ya pueden ser sin
eso.

Si no hiciste caso al readme. Puedes correr el siguiente comando para resetear todo:

	docker-compose down -v

