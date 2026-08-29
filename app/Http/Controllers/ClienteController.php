<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestión de clientes (RF-013). Solo Administrador — protegido por el middleware
 * `rol:administrador` en las rutas. El Empleado puede elegir un cliente existente
 * al registrar una venta, pero no crearlo ni editarlo (decisión G1).
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
        $cliente->load([
            'saldoFavorMovimientos' => fn ($query) => $query->latest('id'),
            'ventasACredito' => fn ($query) => $query->latest('fecha_venta')->latest('id'),
        ]);

        return view('admin.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
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
        $cliente->restore();

        return redirect()
            ->route('admin.clientes.index')
            ->with('status', 'Cliente restaurado.');
    }
}
