<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestión de categorías de producto (A3.2). Solo Administrador — protegido por
 * el middleware `rol:administrador` en las rutas.
 *
 * "Eliminar" es un soft-delete y solo se permite si la categoría no tiene
 * productos activos (Bloque B2). El prefijo de una categoría eliminada puede
 * reutilizarse gracias al índice único sobre la columna generada `activo`.
 */
class CategoriaController extends Controller
{
    public function index(): View
    {
        $categorias = Categoria::withTrashed()
            ->withCount('productos')
            ->orderBy('nombre')
            ->get()
            ->sortBy(fn (Categoria $c) => $c->trashed() ? 1 : 0)
            ->values();

        return view('admin.categorias.index', compact('categorias'));
    }

    public function create(): View
    {
        return view('admin.categorias.create');
    }

    public function store(StoreCategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated());

        return redirect()
            ->route('admin.categorias.index')
            ->with('status', 'Categoría creada.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('admin.categorias.edit', compact('categoria'));
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()
            ->route('admin.categorias.index')
            ->with('status', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->productos()->exists()) {
            return back()->with('error', 'No se puede eliminar una categoría con productos activos.');
        }

        $categoria->delete();

        return redirect()
            ->route('admin.categorias.index')
            ->with('status', 'Categoría eliminada.');
    }

    public function restore(Categoria $categoria): RedirectResponse
    {
        $categoria->restore();

        return redirect()
            ->route('admin.categorias.index')
            ->with('status', 'Categoría restaurada.');
    }
}
