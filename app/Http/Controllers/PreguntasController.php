<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager as DB;

class PreguntasController extends BaseController
{
    private $estados = ['Publicada', 'Expirada', 'Derogada', 'Obsoleta'];

    public function corregirTest(Request $request)
    {
        $params = new \stdClass();
        $input = $request->all();
        foreach ($input as $filter => $value) {
            $params->$filter = $value;
        }

        $total_preguntas_falladas = 0;
        $total_preguntas_acertadas = 0;
        $total_preguntas_no_contestadas = 0;

        if (isset($params->preguntas)) {
            $preguntas_json = $params->preguntas;
            foreach ($preguntas_json as $pregunta_json) {
                $acertada = 1; //Asumimos la pregunta como acertada ya que cuando exista un fallo ya siempre se marcará como fallada.                
                $fallos = 0;
                $respondida = false; //Esta variable determina si el usuario ha contestado la pregunta.     
                if (isset($pregunta_json["marcado"]) && $params->bloqueID == 1 && $params->oposicionID == 2) {
                    if (!isset($params->falladas)) {
                        $params->falladas = 0;
                    }
                    if (!isset($params->acertadas)) {
                        $params->acertadas = 0;
                    }
                    if ($pregunta_json["marcado"] == 1 && count($pregunta_json["respuestas"]) == 0) {
                        $acertada = 1;
                        $contestada = NULL;
                        $params->acertadas = $params->acertadas + 1;
                        $respondida = true;
                    } else if ($pregunta_json["marcado"] == 1 && count($pregunta_json["respuestas"]) > 0) {
                        foreach ($pregunta_json["respuestas"] as $respuesta) {
                            $acertada = -1;
                            $contestada = $respuesta["id"];
                        }
                        $params->falladas = $params->falladas + 1;
                        $fallos++;
                        $respondida = true;
                    } else if ($pregunta_json["marcado"] == -1 && count($pregunta_json["respuestas"]) > 0) {
                        foreach ($pregunta_json["respuestas"] as $respuesta) {
                            $acertada = 1;
                            $contestada = $respuesta["id"];
                            $params->acertadas = $params->acertadas + 1;
                        }
                        $respondida = true;
                    } else if ($pregunta_json["marcado"] == -1 && count($pregunta_json["respuestas"]) == 0) {
                        $acertada = -1;
                        $params->falladas = $params->falladas + 1;
                        $fallos++;
                        //$contestada = 0;
                        $respondida = true;
                    } else {
                        $acertada = 0;
                        //$contestada = 0;

                    }
                } else {
                    $count = 0;
                    if (!isset($params->falladas)) {
                        $params->falladas = 0;
                    }
                    if (!isset($params->acertadas)) {
                        $params->acertadas = 0;
                    }
                    foreach ($pregunta_json["respuestas"] as $respuesta) {
                        if ($respuesta["correcta"] == 1 && $respuesta["contestada"] == true) {
                            //No marcamos la pregunta como acertada porque ya se considera en un inicio como acertada
                            $contestada = $respuesta["id"];
                            $params->acertadas = $params->acertadas + 1;
                            $respondida = true; //Usamos esta variable para ortografía,  en el caso de que no se seleccione ninguna respuesta.
                        } else if ($respuesta["contestada"] == true && $respuesta["correcta"] == 0) {
                            $contestada = $respuesta["id"];
                            $acertada = 0; //Una vez marcada como fallada ya la pregunta no puede ser nunca acertada
                            $params->falladas = $params->falladas + 1;
                            $fallos++;
                            $respondida = true; //Usamos esta variable para ortografía,  en el caso de que no se seleccione ninguna respuesta.
                        } else if ($respuesta["correcta"] == 1 &&  $respuesta["contestada"] == false) {
                            //Sólo se tiene en cuenta en Ortografía
                            if ($params->bloqueID == 1 && $params->oposicionID == 1) {
                                $params->falladas = $params->falladas + 1;
                                $contestada = 0;
                                $fallos++;
                            }
                            $acertada = 0; //Una vez marcada como fallada ya la pregunta no puede ser nunca acertada          
                        } else if ($respuesta["correcta"] == 0 &&  $respuesta["contestada"] == false) {
                            //Sólo se tiene en cuenta en Ortografía
                            if ($params->bloqueID == 1 && $params->oposicionID == 1) {
                                $params->acertadas = $params->acertadas + 1;
                                $contestada = 0;
                                //No marcamos la pregunta como acertada porque ya se considera en un inicio como acertada
                            }
                        }
                    }
                }
                //Actualizamos contadores de preguntas
                if (!$respondida) {
                    $total_preguntas_no_contestadas++;
                } else if ($acertada == 1) {
                    $total_preguntas_acertadas++;
                } else {
                    $total_preguntas_falladas++;
                }
            }
        }

        echo json_encode(array(
            "preguntas_acertadas" => $total_preguntas_acertadas,
            "total_preguntas_no_contestadas" => $total_preguntas_no_contestadas,
            "total_preguntas_falladas" => $total_preguntas_falladas,
        ));
    }

    public function listarTest($oposicionId, $tipoId, $bloqueId, $estado = null)
    {
        // Si el estado existe y el estado solicitado no es valido entre los disponibles
        // Se retorna un mensaje de advertencia al usuario
        if ($estado && !in_array($estado, $this->estados)) {
            return response()->json([
                'mensaje' => 'El estado solicitado no es valido'
            ]);
        }

        // Se genera el query principal
        $query = "SELECT pt.testID, pct.nombre
            FROM preguntas_config_tests pct
            JOIN preguntas_tests pt ON pt.testID = pct.id
            JOIN preguntas_bloque pb ON pb.preguntaID = pt.preguntaID
            JOIN preguntas p ON p.id = pt.preguntaID
            WHERE pt.oposicionID = {$oposicionId}
                AND pb.bloqueID = {$bloqueId}
                AND pct.test_tipoID = {$tipoId}";

        // Si se recibe el filtro de esatdo, se agrega la condicion al query principal
        if ($estado) $query .= " AND p.estado = '" . $estado . "' ";

        // Finaliza el query
        $query .= " GROUP BY pt.testID, pct.nombre
            HAVING COUNT(*) >= 5";

        // Ejecuta y retorna los test que complen la conficion        
        $results = app('db')->select($query);
        return response()->json([
            'test_con_mas_de_5_preguntas' => $results
        ]);
    }

    public function actualizarEstadoPregunta($preguntaId, $nuevoEstado)
    {
        // BEGIN VALIDACIONES
        // Si el estado existe y el estado solicitado no es valido entre los disponibles
        // Se retorna un mensaje de advertencia al usuario
        if (!in_array($nuevoEstado, $this->estados)) {
            return response()->json([
                'mensaje' => 'El estado solicitado no es valido'
            ]);
        }
        // Se verifica que el ID de la pregunta envido existe en la base de datos
        // Sino existe, se retorna un mensaje de advertencia al usuario
        $pregunta = app('db')->table('preguntas')->where('id', $preguntaId)->first();
        if (!$pregunta) {
            return response()->json([
                'mensaje' => 'El id solicitado no es valido'
            ]);
        }
        // Se verifca que el estado a cambiar sea distinto al estado actual de la pregunta
        // si el estado enviado es el mismo que el estado actual de la preguna
        // se retorna un mensaje de advertencia al usuario
        if ($pregunta->estado == $nuevoEstado) {
            return response()->json([
                'mensaje' => 'El estado actual de la pregunta es ' . $nuevoEstado . '. No se han ralizado cambios'
            ]);
        }
        // END Validaciones

        // Se comienza una transaccion, para garantizar la integridad de los datos
        app('db')->beginTransaction();
        try {
            // Se actualiza el estado de la pregunta en la base de datos
            app('db')->table('preguntas')->where('id', $preguntaId)->update(['estado' => $nuevoEstado]);

            // Se agrega un nuevo registro en el historico de estatus de preguntas
            $historial = app('db')->table('historical_status_preguntas')->insert(
                [
                    'pregunta_id' => $preguntaId,
                    'estado' => $nuevoEstado,
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                ]
            );
            // Si todo sale bien, se realiza el commit de los datos
            // se ejecutan todos los querys de manera segura            
            app('db')->commit();

            // Si se genera el historial, se retorna un mensaje de exito
            if ($historial) {
                return response()->json([
                    'mensaje' => 'El estado ha sido actualizado correctamente'
                ]);
                // en caso contrario, se retorna un mensaje de advertencia al usuario
            } else {
                return response()->json([
                    'mensaje' => 'Ocurrio un error, intente mas tarde'
                ]);
            }
        } catch (\Exception $e) {
            // Si ocurre algun error durante la transaccion, se ejecuta un rollback            
            app('db')->rollback();
            // se retorna un mensaje de advertencia al usuario
            return response()->json([
                'mensaje' => 'Ocurrio un error, intente mas tarde'
            ]);
        }
    }

    public function historialEstadosPregunta($preguntaId)
    {
        // Si el id de la pregunta solicitada no existe en la base de datos
        // Se retorna un mensaje de advertencia al usuario
        $pregunta = app('db')->table('preguntas')->where('id', $preguntaId)->first();
        if (!$pregunta) {
            return response()->json([
                'mensaje' => 'El id solicitado no es valido'
            ]);
        }

        // Se recupera de la base de datos el historial de la pregunta
        $historial = app('db')->table('historical_status_preguntas')->where('pregunta_id', $preguntaId)->orderBy('created_at', 'desc')->get();
        // Si el historial tiene al menos 1 registro
        // se retorna el historial, junto al estado actual de la pregunta y un mensaje de exito        
        if (count($historial)) {
            return response()->json([
                'mensaje' => 'Histoial obtenido exitosamente',
                'estado_actual' => $pregunta->estado,
                'historial' => $historial
            ]);
            // En caso contrario, si el historial no tiene registros
            // Se retorna un mensaje de advertencia al usuario junto al estado actual de la pregunta
        } else {
            return response()->json([
                'mensaje' => 'Pregunta no tiene historial',
                'estado_actual' => $pregunta->estado,
            ]);
        }
    }

    public function corregirTestV2(Request $request)
    {
        $params = new \stdClass();
        $input = $request->all();
        foreach ($input as $filter => $value) {
            $params->$filter = $value;
        }

        $total_preguntas_acertadas = 0;
        $total_preguntas_no_contestadas = 0;

        if (isset($params->preguntas)) {
            $preguntas_json = $params->preguntas;
            foreach ($preguntas_json as $pregunta_json) {
                // Movemos la logica de validar a la pregunta a otra funcion
                // Para no sobrecargar el metodo
                $resultado = $this->validarPregunta($pregunta_json, $params->bloqueID, $params->oposicionID);

                //Actualizamos contadores de preguntas
                if (!$resultado['respondida']) {
                    $total_preguntas_no_contestadas++;
                } else if ($resultado['acertada']) {
                    $total_preguntas_acertadas++;
                }
            }
        }

        return response()->json([
            "preguntas_acertadas" => $total_preguntas_acertadas,
            "total_preguntas_no_contestadas" => $total_preguntas_no_contestadas,
            "total_preguntas_falladas" => count($params->preguntas) - $total_preguntas_acertadas - $total_preguntas_no_contestadas,
        ]);
    }

    private function validarPregunta($pregunta, $bloqueId, $oposicionId)
    {
        $acertada = true; //Asumimos la pregunta como acertada ya que cuando exista un fallo ya siempre se marcará como fallada.                
        $respondida = false; //Esta variable determina si el usuario ha contestado la pregunta.     
        if (isset($pregunta["marcado"]) && $bloqueId == 1 && $oposicionId == 2) {
            $respondida = true;

            if ($pregunta["marcado"] == 1 && count($pregunta["respuestas"]) > 0) {
                $acertada = false;
            } else if ($pregunta["marcado"] == -1 && count($pregunta["respuestas"]) == 0) {
                $acertada = false;
            } else {
                $acertada = false;
                $respondida = false;
            }
        } else {
            foreach ($pregunta["respuestas"] as $respuesta) {
                if ($respuesta["contestada"] == true) {
                    $respondida = true; //Usamos esta variable para ortografía,  en el caso de que no se seleccione ninguna respuesta.
                    $acertada = ($respuesta["correcta"] == 1);
                } else {
                    if ($respuesta["correcta"] == 1) {
                        $acertada = 0; //Una vez marcada como fallada ya la pregunta no puede ser nunca acertada          
                    }
                }
            }
        }

        return [
            'acertada' => $acertada,
            'respondida' => $respondida
        ];
    }
}
