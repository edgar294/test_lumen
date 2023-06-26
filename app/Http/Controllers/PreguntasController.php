<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager as DB;

class PreguntasController extends BaseController
{
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
                $contestada = null;
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

    public function listarTest(Request $request, $oposicionId, $tipoId, $bloqueId, $status = null)
    {
        $results = app('db')->select(
            "SELECT pt.testID, pct.nombre
            FROM preguntas_config_tests pct
            JOIN preguntas_tests pt ON pt.testID = pct.id
            JOIN preguntas_bloque pb ON pb.preguntaID = pt.preguntaID
            WHERE pt.oposicionID = {$oposicionId}
                AND pb.bloqueID = {$bloqueId}
                AND pct.test_tipoID = {$tipoId}
            GROUP BY pt.testID, pct.nombre
            HAVING COUNT(*) >= 5"
        );
        return response()->json([
            'test_con_mas_de_5_preguntas' => $results
        ]);
    }

    public function actualizarEstadoPregunta($preguntaId, $nuevoEstado)
    {
        $estados = ['Publicada', 'Expirada', 'Derogada', 'Obsoleta'];
        if (!in_array($nuevoEstado, $estados)) {
            return response()->json([
                'mensaje' => 'El estado solicitado no es valido'
            ]);
        }

        $pregunta = app('db')->table('preguntas')->where('id', $preguntaId)->first();
        if (!$pregunta) {
            return response()->json([
                'mensaje' => 'El id solicitado no es valido'
            ]);
        }

        if ($pregunta->estado == $nuevoEstado) {
            return response()->json([
                'mensaje' => 'El estado actual de la pregunta es ' . $nuevoEstado . '. No se han ralizado cambios'
            ]);
        }


        app('db')->beginTransaction();
        try {
            app('db')->table('preguntas')->where('id', $preguntaId)->update(['estado' => $nuevoEstado]);

            $historial = app('db')->table('historical_status_preguntas')->insert(
                [
                    'pregunta_id' => $preguntaId,
                    'estado' => $nuevoEstado,
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                ]
            );
            
            app('db')->commit();

            if ($historial) {
                return response()->json([
                    'mensaje' => 'El estado ha sido actualizado correctamente'
                ]);
            } else {
                return response()->json([
                    'mensaje' => 'Ocurrio un error, intente mas tarde'
                ]);
            }
        } catch (\Exception $e) {
            app('db')->rollback();
            return response()->json([
                'mensaje' => 'Ocurrio un error, intente mas tarde'
            ]);
        }
    }

    public function historialEstadosPregunta($preguntaId)
    {
        $pregunta = app('db')->table('preguntas')->where('id', $preguntaId)->first();
        if (!$pregunta) {
            return response()->json([
                'mensaje' => 'El id solicitado no es valido'
            ]);
        }

        $historial = app('db')->table('historical_status_preguntas')->where('pregunta_id', $preguntaId)->orderBy('created_at', 'desc')->get();
        if (count($historial)){
            return response()->json([
                'mensaje' => 'Histoial obtenido exitosamente',
                'estado_actual' => $pregunta->estado,
                'historial' => $historial
            ]);
        } else {
            return response()->json([
                'mensaje' => 'Pregunta no tiene historial',
                'estado_actual' => $pregunta->estado,
            ]);
        }
    }
}
