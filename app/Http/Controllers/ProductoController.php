<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Gestión de productos del catálogo (RF-003). Solo Administrador.
 *
 * El alta crea el producto y su primera variante en una sola transacción (A3:
 * un producto siempre tiene al menos una variante). El `codigo_interno` se
 * autogenera a partir del prefijo de la categoría y no se edita después.
 */
class ProductoController extends Controller
{
    public function index(): View
    {
        $productos = Producto::withTrashed()
            ->with('categoria')
            ->withSum('variantes as stock_total', 'stock')
            ->withCount(['variantes as variantes_bajas_count' => fn ($query) => $query
                ->whereColumn('variantes.stock', '<=', 'productos.umbral_stock_bajo')])
            ->orderBy('nombre')
            ->paginate(20);

        return view('admin.productos.index', compact('productos'));
    }

    public function create(): View|RedirectResponse
    {
        $categorias = Categoria::orderBy('nombre')->get();

        if ($categorias->isEmpty()) {
            return redirect()
                ->route('admin.categorias.create')
                ->with('error', 'Crea al menos una categoría antes de registrar productos.');
        }

        return view('admin.productos.create', compact('categorias'));
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['foto', 'talla', 'color']);

        $producto = DB::transaction(function () use ($request, $datos) {
            // Se bloquea la categoría para serializar las altas simultáneas de la
            // misma categoría: así el correlativo del código nunca se repite.
            $categoria = Categoria::whereKey($datos['categoria_id'])->lockForUpdate()->firstOrFail();

            $datos['codigo_interno'] = Producto::generarCodigoInterno($categoria);

            if ($request->hasFile('foto')) {
                $datos['foto'] = $request->file('foto')->store('productos', 'public');
            }

            $producto = Producto::create($datos);

            $producto->variantes()->create([
                'talla' => trim((string) $request->validated('talla')),
                'color' => trim((string) $request->validated('color')),
            ]);

            return $producto;
        });

        return redirect()
            ->route('admin.productos.show', $producto)
            ->with('status', "Producto creado con código {$producto->codigo_interno}.");
    }

    public function show(Producto $producto): View
    {
        $producto->load('categoria', 'variantes');

        return view('admin.productos.show', compact('producto'));
    }

    public function edit(Producto $producto): View
    {
        return view('admin.productos.edit', compact('producto'));
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $datos = $request->safe()->except('foto');

        if ($request->hasFile('foto')) {
            if ($producto->foto) {
                Storage::disk('public')->delete($producto->foto);
            }
            $datos['foto'] = $request->file('foto')->store('productos', 'public');
        }

        $producto->update($datos);

        return redirect()
            ->route('admin.productos.show', $producto)
            ->with('status', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        // La transacción abarca el producto y la cascada a sus variantes.
        DB::transaction(fn () => $producto->delete());

        return redirect()
            ->route('admin.productos.index')
            ->with('status', 'Producto eliminado.');
    }

    public function restore(Producto $producto): RedirectResponse
    {
        DB::transaction(fn () => $producto->restore());

        return redirect()
            ->route('admin.productos.index')
            ->with('status', 'Producto restaurado.');
    }
}
