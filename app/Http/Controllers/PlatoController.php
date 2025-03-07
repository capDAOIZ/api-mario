<?php

namespace App\Http\Controllers;

use App\Models\Plato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlatoController extends Controller
{
    /**
     * Obtener todos los platos con filtros opcionales de precio.
     *
     * @OA\Get(
     *     path="/api/platos",
     *     summary="Obtener platos",
     *     description="Obtiene todos los platos, con la opción de filtrar por precio.",
     *     @OA\Parameter(
     *         name="min_precio",
     *         in="query",
     *         description="Precio mínimo",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="max_precio",
     *         in="query",
     *         description="Precio máximo",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de platos",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Plato"))
     *     )
     * )
     */
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

    /**
     * Crear o actualizar un plato.
     *
     * @OA\Post(
     *     path="/api/platos",
     *     summary="Crear o actualizar un plato",
     *     description="Crea un nuevo plato o actualiza uno existente.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Plato")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plato creado o actualizado",
     *         @OA\JsonContent(ref="#/components/schemas/Plato")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
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

    /**
     * Obtener un plato por ID.
     *
     * @OA\Get(
     *     path="/api/platos/{id}",
     *     summary="Obtener un plato por ID",
     *     description="Obtiene los detalles de un plato por su ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del plato",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plato encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Plato")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plato no encontrado"
     *     )
     * )
     */
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
            'foto' => 'data:image/jpeg;base64,' . base64_encode($plato->foto)
        ], 200);
    }

    /**
     * Actualizar un plato.
     *
     * @OA\Put(
     *     path="/api/platos/{id}",
     *     summary="Actualizar un plato",
     *     description="Actualiza los detalles de un plato existente.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del plato",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Plato")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plato actualizado",
     *         @OA\JsonContent(ref="#/components/schemas/Plato")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plato no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }

        $validate = Validator::make($request->all(), [
            'nombre' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => false, 'message' => $validate->errors()], 422);
        }

        if ($request->has('nombre')) {
            $plato->nombre = $request->nombre;
        }

        if ($request->has('precio')) {
            $plato->precio = $request->precio;
        }

        if ($request->hasFile('foto')) {
            $plato->foto = file_get_contents($request->file('foto')->getRealPath());
        }

        $plato->save();

        return response()->json(['status' => true, 'message' => 'Plato actualizado con éxito.', 'plato' => $plato], 200);
    }

    /**
     * Eliminar un plato.
     *
     * @OA\Delete(
     *     path="/api/platos/{id}",
     *     summary="Eliminar un plato",
     *     description="Elimina un plato por su ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del plato",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plato eliminado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plato no encontrado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }

        $plato->delete();
    }
}
