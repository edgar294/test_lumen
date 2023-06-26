# Prueba Lumen

Pequena prueba tecnica desarrollada con Lumen y Mysql

## Puntos a considerar:

Para la correcta ejecucion de las actualizaciones se debe configurar adecuadamente las credenciales
de la base de datos. Estas se configuran haciendo una copia el archivo _.env.example_ o renombrandolo 
como _.env_ y sobrecribiendo los valores adecuados en las variables __DB_DATABASE, DB_USERNAME y DB_PASSWORD__

Paso siguiente es ejecutar el comando
__php artisan migrate__

## Ejecutar en local:

Para ejecutar el proyecto, basta moverse al path del proyecto, a la carpeta public y desde alli ejecutar
el siguiente comando:

    cd /path/del/proyecto/public/
    php -S localhost:2000

Esto desplegara un servidor en localhost en el puerto 2000 y se puede acceder desde [http://localhost:2000](http://localhost:2000)

## Endpoints agregados:

1.- "api/test/oposicion/{oposicionId}/tipo/{tipoId}/bloque/{bloqueId}"

    Este endpoint sirve para consultar los test que tiene al menos 5 preguntas, filtrados a traves de:

    - Oposicion: Indicar el ID de la Oposicion
    - Tipo: Indicar el ID del tipo de prueba    
    - Bloque: Indicar el ID del bloque de la prueba

    Retorna todas las pruebas que tiene al menos 5 preguntas.

2.- "api/test/oposicion/{oposicionId}/tipo/{tipoId}/bloque/{bloqueId}/estado/{estado}"
    
    Este endpoint sirve para consultar los test que tiene al menos 5 preguntas, filtrados a traves de:

    - Oposicion: Indicar el ID de la Oposicion
    - Tipo: Indicar el ID del tipo de prueba    
    - Bloque: Indicar el ID del bloque de la prueba
    - Estado: Estado que deben tener las preguntas en la prueba

    Retorna todas las pruebas que tiene al menos 5 preguntas, cuyas preguntas cumplan el estado indicado.
    Los estados posibles son:
    -- Publicada
    -- Expirada
    -- Derogada
    -- Obsoleta

    CUalquier otro estado sera omitido

3.- "api/pregunta/{preguntaId}/actualizar/estado/{nuevoEstado}"

    Este Endpoint sirve para cambiar el estado de una pregunta, recibe como parametros:

    - preguntaId: El ID de la pregunta a cambiar el estado
    - nuevoEstado: El nuevo estado que se le asignara a la pregunta

    Es importante destacar que se cumplen las siguientes validaciones:

    -- El ID de la pregunta sea valido y exista
    -- El nuevo estado asignado sea un estado valido ('Publicada', 'Expirada', 'Derogada', 'Obsoleta')
    -- El nuevo estado de la pregunta sea diferente al estado actual de la pregunta.

4.- "api/pregunta/{preguntaId}/historico"

    Este endpoint sirve para consultar el historico de los cambios de estado que ha tenido una pregunta,
    Recibe como parametros:

    - preguntaId: El ID de la pregunta a consultar el historico de cambios.



