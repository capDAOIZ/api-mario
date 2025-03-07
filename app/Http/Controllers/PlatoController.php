<?php

namespace App\Http\Controllers;

use App\Models\Plato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlatoController extends Controller
{
    public function index(Request $request)
    {
        $query = Plato::query();

        if ($request->has('min_precio')) {
            $query->where('precio', '>=', $request->min_precio);
        }

        if ($request->has('max_precio')) {
            $query->where('precio', '<=', $request->max_precio);
        }

        return response()->json($query->get(), 200);
    }

    public function store(Request $request)
{
    try {
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => false, 'message' => $validate->errors()], 422);
        }

        $plato = Plato::updateOrCreate(
            ['nombre' => $request->nombre],
            [
                'precio' => $request->precio,
                'foto' => $request->hasFile('foto') ? file_get_contents($request->file('foto')->getRealPath()) : null
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Plato creado o actualizado con éxito.',
            'plato' => [
                'id' => $plato->id,
                'nombre' => $plato->nombre,
                'precio' => $plato->precio,
                'foto' => $plato->foto ? base64_encode($plato->foto) : null
            ]
        ], 201);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}



    public function show($id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }

        return response()->json([
            'id' => $plato->id,
            'nombre' => $plato->nombre,
            'precio' => $plato->precio,
            'foto' => 'data:image/jpeg;base64,' . base64_encode($plato->foto) // Convertir BLOB a Base64
        ], 200);
    }

    public function update(Request $request, $id)
{
    $plato = Plato::find($id);
    if (!$plato) {
        return response()->json(['message' => 'Plato no encontrado'], 404);
    }

    $validate = Validator::make($request->all(), [
        'nombre' => 'string|max:255',
        'precio' => 'numeric',
        'foto' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
    ]);

    if ($validate->fails()) {
        return response()->json(['status' => false, 'message' => $validate->errors()], 422);
    }

    // Asignar valores solo si están presentes
    $plato->fill([
        'nombre' => $request->nombre ?? $plato->nombre,
        'precio' => $request->precio ?? $plato->precio,
    ]);

    // Si se envía una nueva imagen, actualizar el campo foto
    if ($request->hasFile('foto')) {
        $plato->foto = file_get_contents($request->file('foto')->getRealPath());
    }

    // Verificar antes de guardar
    Log::info('Datos antes de guardar: ', $plato->toArray());

    // Guardamos los cambios
    $plato->save();

    // Verificar después de guardar
    Log::info('Datos después de guardar: ', $plato->toArray());

    return response()->json(['status' => true, 'message' => 'Plato actualizado con éxito.', 'plato' => $plato], 200);
}


    public function destroy($id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }

        $plato->delete();
    }
}
