<?php

namespace App\Http\Controllers;

use App\Models\Plato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048', // La imagen es opcional
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => false, 'message' => $validate->errors()], 422);
        }

        $plato = new Plato();
        $plato->nombre = $request->nombre;
        $plato->precio = $request->precio;

        // Si se sube una imagen, la guardamos en formato BLOB
        if ($request->hasFile('foto')) {
            $plato->foto = file_get_contents($request->file('foto')->getRealPath());
        } else {
            $plato->foto = null; // Si no hay imagen, se guarda NULL
        }

        $plato->save();

        return response()->json([
            'status' => true,
            'message' => 'Plato creado con éxito.',
            'plato' => [
                'id' => $plato->id,
                'nombre' => $plato->nombre,
                'precio' => $plato->precio,
                'foto' => $plato->foto ? base64_encode($plato->foto) : null // Convertimos a base64 si existe
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
            'foto' => 'image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => false, 'message' => $validate->errors()], 422);
        }

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $plato->foto = file_get_contents($file->getRealPath()); // Guardar la nueva imagen en binario
        }

        $plato->update($request->only(['nombre', 'precio']));

        return response()->json(['status' => true, 'message' => 'Plato actualizado con éxito.', 'plato' => $plato], 200);
    }

    public function destroy($id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }

        $plato->delete();
        return response()->json(['message' => 'Plato eliminado'], 200);
    }
}
