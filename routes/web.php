<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function() {
    return 'Hola, estoy funcionando';
});

$router->group(['prefix'=>'api','json.response'], function() use($router){
    $router->post('/corregirTest','PreguntasController@corregirTestV2'); 
    $router->get('/test/oposicion/{oposicionId}/tipo/{tipoId}/bloque/{bloqueId}',   'PreguntasController@listarTest');
    $router->get('/test/oposicion/{oposicionId}/tipo/{tipoId}/bloque/{bloqueId}/estado/{estado}',   'PreguntasController@listarTest');
    $router->get('/pregunta/{preguntaId}/actualizar/estado/{nuevoEstado}',  'PreguntasController@actualizarEstadoPregunta');
    $router->get('/pregunta/{preguntaId}/historico',    'PreguntasController@historialEstadosPregunta');
});
