<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestión de clientes (RF-013). Empleado y Administrador; la {@see ClientePolicy}
 * decide el detalle (decisión G1 revisada 2026-08-29): el Empleado puede crear,
 * consultar y editar clientes, pero **no** eliminarlos ni restaurarlos.
 *
 * "Eliminar" es un soft-delete y solo se permite si el cliente no tiene crédito
 * pendiente ni saldo a favor sin consumir (punto 6, Sprint 4). La `cedula` de un
 * cliente eliminado puede reutilizarse gracias al índice único sobre la columna
 * generada `activo`.
 */
class ClienteController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Cliente::class);

        $clientes = Cliente::withTrashed()
            ->withCount('ventasACredito')
            ->orderBy('nombre')
            ->get()
            ->sortBy(fn (Cliente $c) => $c->trashed() ? 1 : 0)
            ->values();

        return view('admin.clientes.index', compact('clientes'));
    }

    public function create(): View
    {
        $this->authorize('create', Cliente::class);

        return view('admin.clientes.create');
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()
            ->route('admin.clientes.show', $cliente)
            ->with('status', 'Cliente registrado.');
    }

    public function show(Cliente $cliente): View
    {
        $this->authorize('view', $cliente);

        $cliente->load([
            'saldoFavorMovimientos' => fn ($query) => $query->latest('id'),
            'ventasACredito' => fn ($query) => $query->latest('fecha_venta')->latest('id'),
        ]);

        return view('admin.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        $this->authorize('update', $cliente);

        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()
            ->route('admin.clientes.show', $cliente)
            ->with('status', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->authorize('delete', $cliente);

        if (! $cliente->puedeEliminarse()) {
            return back()->with('error', 'No se puede eliminar un cliente con crédito pendiente o saldo a favor.');
        }

        $cliente->delete();

        return redirect()
            ->route('admin.clientes.index')
            ->with('status', 'Cliente eliminado.');
    }

    public function restore(Cliente $cliente): RedirectResponse
    {
        $this->authorize('restore', $cliente);

        $cliente->restore();

        return redirect()
            ->route('admin.clientes.index')
            ->with('status', 'Cliente restaurado.');
    }
}
