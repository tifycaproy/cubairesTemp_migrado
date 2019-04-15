<?php

namespace App\Http\Controllers\Backend;

use App\Servicios;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\DetallesClientes as DetallesClientes;
use App\Solicitudes as Solicitudes;
use App\Paises;
use App\User;
use App\Configuraciones;
use App\Mail\Notificacion;
use App\Mail\Respuestas;

class SolicitudesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($mensaje)
    {
        $vista_porconfirmar=NULL;
        $vista_confirmado=NULL;
        $vista_rechazado=NULL;
        switch ($mensaje) {
            case 0:
            $vista_porconfirmar="active";
                break;
            case 1:
            $vista_confirmado="active";
                break;
            case 2:
                $vista_rechazado="active";
                break;
            default:
            
                # code...
                break;
        }
        $solicitudes= Solicitudes::select(DB::raw('solicitudes.id, titulo_servicio, numero_adulto, numero_nino, fecha_desde, fecha_hasta, observacion, solicitudes.created_at, estatus_solicitud, name'))
                    ->join('servicios', 'servicios.id', '=', 'solicitudes.servicio_id')
                    ->join('detalles_clientes', 'detalles_clientes.id', '=', 'solicitudes.detalle_cliente_id')
                    ->join('role_user', 'role_user.id', '=', 'detalles_clientes.role_user_id')
                    ->join('users', 'users.id', '=', 'role_user.user_id')
                    ->get();
        $porconfirmar=array();
        $confirmados=array();
        $rechazados=array();
        // dd($solicitudes);
        foreach ($solicitudes as $key => $value) {
            
            switch ($value["estatus_solicitud"]) {
                case 1:
                $confirmados[$key]["solicitud_id"]=$value["id"];
                $confirmados[$key]["name"]=$value["name"];
                $confirmados[$key]["titulo_servicio"]=$value["titulo_servicio"];
                $confirmados[$key]["fecha_desde"]=$value["fecha_desde"];
                $confirmados[$key]["fecha_hasta"]=$value["fecha_hasta"];
                $confirmados[$key]["numero_adulto"]=$value["numero_adulto"];
                $confirmados[$key]["numero_nino"]=$value["numero_nino"];
                $confirmados[$key]["observacion"]=$value["observacion"];
                $confirmados[$key]["created_at"]=$value["created_at"];
                $confirmados[$key]["estatus_solicitud"]=$value["estatus_solicitud"];
                    break;
                    case 0:
                    $porconfirmar[$key]["solicitud_id"]=$value["id"];
                    $porconfirmar[$key]["name"]=$value["name"];
                    $porconfirmar[$key]["titulo_servicio"]=$value["titulo_servicio"];
                    $porconfirmar[$key]["fecha_desde"]=$value["fecha_desde"];
                    $porconfirmar[$key]["fecha_hasta"]=$value["fecha_hasta"];
                    $porconfirmar[$key]["numero_adulto"]=$value["numero_adulto"];
                    $porconfirmar[$key]["numero_nino"]=$value["numero_nino"];
                    $porconfirmar[$key]["observacion"]=$value["observacion"];
                    $porconfirmar[$key]["created_at"]=$value["created_at"];
                    $porconfirmar[$key]["estatus_solicitud"]=$value["estatus_solicitud"];
                    break;
                    case 2:
                    $rechazados[$key]["solicitud_id"]=$value["id"];
                    $rechazados[$key]["name"]=$value["name"];
                    $rechazados[$key]["titulo_servicio"]=$value["titulo_servicio"];
                    $rechazados[$key]["fecha_desde"]=$value["fecha_desde"];
                    $rechazados[$key]["fecha_hasta"]=$value["fecha_hasta"];
                    $rechazados[$key]["numero_adulto"]=$value["numero_adulto"];
                    $rechazados[$key]["numero_nino"]=$value["numero_nino"];
                    $rechazados[$key]["observacion"]=$value["observacion"];
                    $rechazados[$key]["created_at"]=$value["created_at"];
                    $rechazados[$key]["estatus_solicitud"]=$value["estatus_solicitud"];
                    break;
                default:
                   
                    break;
            }
        }    
        return view('Backend.solicitudes.index',['porconfirmar'=>$porconfirmar,'confirmados'=>$confirmados,'vista_porconfirmar'=>$vista_porconfirmar,'vista_confirmado'=>$vista_confirmado,'vista_rechazado'=>$vista_rechazado,'rechazados'=>$rechazados]);     
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($solicitudes)
    {
        $solicitud=  Solicitudes::select(DB::raw('solicitudes.id as solicitud_id, email, titulo_servicio, numero_adulto, numero_nino, fecha_desde, fecha_hasta, observacion, solicitudes.created_at, estatus_solicitud, name'))
                    ->join('servicios', 'servicios.id', '=', 'solicitudes.servicio_id')
                    ->join('detalles_clientes', 'detalles_clientes.id', '=', 'solicitudes.detalle_cliente_id')
                    ->join('role_user', 'role_user.id', '=', 'detalles_clientes.role_user_id')
                    ->join('users', 'users.id', '=', 'role_user.user_id')
                    ->where('solicitudes.id',$solicitudes)
                    ->first();         
                    // dd($solicitud);
                    return view('Backend.solicitudes.create',['solicitud'=>$solicitud]);                         
            
            // Mail::to($solicitud->email)->send(new Notificacion($solicitud));
    }

    public function send(Request $request,$solicitud)
    {       
        
        $solicitud=  Solicitudes::select(DB::raw('solicitudes.id, email, titulo_servicio, numero_adulto, numero_nino, fecha_desde, fecha_hasta, observacion, solicitudes.created_at, estatus_solicitud, name'))
                ->join('servicios', 'servicios.id', '=', 'solicitudes.servicio_id')
                ->join('detalles_clientes', 'detalles_clientes.id', '=', 'solicitudes.detalle_cliente_id')
                ->join('role_user', 'role_user.id', '=', 'detalles_clientes.role_user_id')
                ->join('users', 'users.id', '=', 'role_user.user_id')
                ->where('solicitudes.id',$solicitud)
                ->first();

            Mail::to($solicitud->email)->send(new Respuestas($request));

            return redirect()->route("versolicitudes",['mensaje'=>0]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        // dd($request);
        // return view('Frontend.Mail.mail');
        if(Auth::user()){
            if(Auth::user()->hasRole('client')){
                
                $detalles_cliente=User::join('role_user', 'role_user.user_id', '=', 'users.id')
                            ->join('detalles_clientes', 'detalles_clientes.role_user_id', '=', 'role_user.id')
                                ->where('users.id',Auth::user()->id)
                                ->first();
                // dd($detalles_cliente);
                
                $solicitud = new Solicitudes;
                $solicitud->fill($request->input());
                $solicitud->estatus_solicitud = 0;
                $solicitud->detalle_cliente_id = $detalles_cliente->id;
                $solicitud->servicio_id = $id;
                $solicitud->save();
                
                $config = Configuraciones::get();
                $correo = User::where('id',Auth::user()->id)->pluck('email');
                
                // $datos['titulo']="Su registro fue todo un exito";
                // $datos['mensaje']="Pronto nos pondremos en contacto con usted";
                Mail::to($correo)->send(new Notificacion($request));

                return redirect()->route("usuario");
                
            }
            else{
                if(Auth::user()){
                    return redirect()->route("versolicitudes");
                }
                else{
                    return redirect()->route("/");
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Solicitudes  $solicitud
     * @return \Illuminate\Http\Response
     */
    public function show(Solicitudes $solicitudes)
    {

        
    }

    /**
     * Show the form for editing the specified resource.
     *@param  \Illuminate\Http\Request  $request
     * @param  \App\Solicitudes  $solicitud
     * @return \Illuminate\Http\Response
     */
    public function edit(Solicitudes $solicitudes)
    {   

        return view('Backend.form.formtramite');     
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Solicitudes  $solicitud
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$solicitudes,$estatus_solicitud)
    {         
        // dd($estatus_solicitud);
        Solicitudes::where('id', $solicitudes)
                    ->update(['estatus_solicitud'=>$estatus_solicitud]);

        $solicitud=  Solicitudes::select(DB::raw('solicitudes.id, email, titulo_servicio, numero_adulto, numero_nino, fecha_desde, fecha_hasta, observacion, solicitudes.created_at, estatus_solicitud, name'))
                                ->join('servicios', 'servicios.id', '=', 'solicitudes.servicio_id')
                                ->join('detalles_clientes', 'detalles_clientes.id', '=', 'solicitudes.detalle_cliente_id')
                                ->join('role_user', 'role_user.id', '=', 'detalles_clientes.role_user_id')
                                ->join('users', 'users.id', '=', 'role_user.user_id')
                                ->where('solicitudes.id',$solicitudes)
                                ->first();
        
        
        switch ($estatus_solicitud) {           
            case 1:
                $request->request->add(['titulo' => 'Gracias por su preferencia']);
                $request->request->add(['mensaje' => 'Su paquete solicitado ha sido aceptado']);
                break;
            case 2:
                $request->request->add(['titulo' => 'Lo sentimos mucho']);
                $request->request->add(['mensaje' => 'El paquete que usted solicitó no pudo ser procesado']);                
                break;
            
            default:
                # code...
                break;
        }
        
        Mail::to($solicitud->email)->send(new Notificacion($request));

        return redirect()->route("versolicitudes",['mensaje'=>$estatus_solicitud]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Solicitudes  $solicitud
     * @return \Illuminate\Http\Response
     */
    public function destroy(Solicitudes $solicitudes)
    {

    }
}