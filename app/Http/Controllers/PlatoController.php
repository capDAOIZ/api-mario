<?php

namespace App\Http\Controllers;

use App\Models\Plato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/platos",
     *     summary="Obtener todos los platos con opción de filtrado por precio",
     *     tags={"Platos"},
     *     @OA\Parameter(
     *         name="min_precio",
     *         in="query",
     *         description="Filtrar por precio mínimo",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_precio",
     *         in="query",
     *         description="Filtrar por precio máximo",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Response(response=200, description="Lista de platos obtenida exitosamente"),
     *     @OA\Response(response=500, description="Error en el servidor")
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
     * @OA\Post(
     *     path="/api/platos",
     *     summary="Crear un nuevo plato",
     *     tags={"Platos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nombre", "precio", "foto"},
     *                 @OA\Property(property="nombre", type="string", example="Pizza"),
     *                 @OA\Property(property="precio", type="number", example=10.99),
     *                 @OA\Property(property="foto", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Plato creado con éxito"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'foto' => 'required|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => false, 'message' => $validate->errors()], 422);
        }

        $file = $request->file('foto');
        $directory = 'storage/platos';
        $imageName = time() . '.' . $file->getClientOriginalName();
        $file->move(public_path($directory), $imageName);

        $plato = new Plato();
        $plato->nombre = $request->nombre;
        $plato->precio = $request->precio;
        $plato->foto = file_get_contents($request->file('foto')->getRealPath()); // Guarda la imagen en la BD como BLOB
        $plato->save();


        return response()->json(['status' => true, 'message' => 'Plato creado con éxito.', 'plato' => $plato], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/platos/{id}",
     *     summary="Obtener un plato por ID",
     *     tags={"Platos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del plato",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Plato encontrado"),
     *     @OA\Response(response=404, description="Plato no encontrado")
     * )
     */
    public function show($id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }
        return response()->json($plato, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/platos/{id}",
     *     summary="Actualizar un plato",
     *     tags={"Platos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del plato",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="precio", type="number"),
     *                 @OA\Property(property="foto", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Plato actualizado con éxito"),
     *     @OA\Response(response=404, description="Plato no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, $id)
    {
        $plato = Plato::find($id);
        if (!$plato) {
            return response()->json(['message' => 'Plato no encontrado'], 404);
        }

        $validate = Validator::make($request->all(), [
            'nombre' => 'string|max:255',
            'precio' => 'numeric',
            'foto' => 'mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => false, 'message' => $validate->errors()], 422);
        }

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $directory = 'storage/platos';
            $imageName = time() . '.' . $file->getClientOriginalName();
            $file->move(public_path($directory), $imageName);
            $plato->foto = "$directory/$imageName";
        }

        $plato->update($request->only(['nombre', 'precio']));

        return response()->json(['status' => true, 'message' => 'Plato actualizado con éxito.', 'plato' => $plato], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/platos/{id}",
     *     summary="Eliminar un plato",
     *     tags={"Platos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del plato",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Plato eliminado"),
     *     @OA\Response(response=404, description="Plato no encontrado")
     * )
     */
    public function destroy($id)
    {
        $plato = Plato::find($id);
        $plato->delete();
        return response()->json(['message' => 'Plato eliminado'], 200);
    }
}
