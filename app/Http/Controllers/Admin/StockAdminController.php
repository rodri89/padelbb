<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\StockAuditoria;
use App\StockCategoriaProducto;
use App\StockMovimientoStock;
use App\StockProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class StockAdminController extends Controller
{
    public function index()
    {
        $categorias = StockCategoriaProducto::query()->orderBy('nombre')->get();
        $productos = StockProducto::query()
            ->with('categoria')
            ->orderBy('nombre')
            ->get();
        $alertas = $productos->filter(fn (StockProducto $p) => $p->stock_actual <= $p->stock_minimo || $p->stock_actual <= 0);

        return view('bahia_padel.admin.stock.index', compact('categorias', 'productos', 'alertas'));
    }

    public function movimientosData()
    {
        $q = StockMovimientoStock::query()
            ->leftJoin('stock_productos', 'stock_movimientos_stock.stock_producto_id', '=', 'stock_productos.id')
            ->select([
                'stock_movimientos_stock.id',
                'stock_movimientos_stock.created_at',
                'stock_movimientos_stock.tipo_movimiento',
                'stock_movimientos_stock.cantidad',
                'stock_movimientos_stock.cantidad_anterior',
                'stock_movimientos_stock.cantidad_nueva',
                'stock_movimientos_stock.motivo',
                'stock_movimientos_stock.usuario_responsable',
                'stock_productos.nombre as producto_nombre',
            ]);

        return DataTables::of($q)
            ->addColumn('stock_cambio', function ($row) {
                return (int) $row->cantidad_anterior.' → '.(int) $row->cantidad_nueva;
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at
                    ? \Carbon\Carbon::parse($row->created_at)->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : '';
            })
            ->editColumn('motivo', function ($row) {
                return Str::limit((string) ($row->motivo ?? ''), 80);
            })
            ->filterColumn('producto_nombre', function ($query, $keyword) {
                $query->whereRaw('stock_productos.nombre like ?', ['%'.$keyword.'%']);
            })
            ->orderColumn('producto_nombre', 'stock_productos.nombre $1')
            ->removeColumn('id')
            ->removeColumn('cantidad_anterior')
            ->removeColumn('cantidad_nueva')
            ->rawColumns([])
            ->make(true);
    }

    public function storeCategoria(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activa' => 'nullable|boolean',
        ]);
        $data['activa'] = $request->boolean('activa');
        $cat = StockCategoriaProducto::query()->create($data);

        StockAuditoria::registrar(
            'stock_categorias_productos',
            (int) $cat->id,
            'INSERT',
            Auth::user()?->email,
            null,
            $cat->toArray(),
            $request->ip()
        );

        return redirect()->route('adminstock')->with('success', 'Categoría creada.');
    }

    public function updateCategoria(Request $request, StockCategoriaProducto $categoria)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activa' => 'nullable|boolean',
        ]);
        $data['activa'] = $request->boolean('activa');
        $antes = $categoria->toArray();
        $categoria->update($data);

        StockAuditoria::registrar(
            'stock_categorias_productos',
            (int) $categoria->id,
            'UPDATE',
            Auth::user()?->email,
            $antes,
            $categoria->fresh()->toArray(),
            $request->ip()
        );

        return redirect()->route('adminstock')->with('success', 'Categoría actualizada.');
    }

    public function storeProducto(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'stock_categoria_id' => 'required|exists:stock_categorias_productos,id',
            'precio_unitario' => 'required|numeric|min:0',
            'stock_actual' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);
        $data['activo'] = $request->boolean('activo');

        $producto = StockProducto::query()->create($data);

        StockMovimientoStock::query()->create([
            'stock_producto_id' => $producto->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => (int) $data['stock_actual'],
            'cantidad_anterior' => 0,
            'cantidad_nueva' => (int) $data['stock_actual'],
            'motivo' => 'Stock inicial al crear producto',
            'usuario_responsable' => Auth::user()?->email,
            'created_at' => now(),
        ]);

        StockAuditoria::registrar(
            'stock_productos',
            (int) $producto->id,
            'INSERT',
            Auth::user()?->email,
            null,
            $producto->toArray(),
            $request->ip()
        );

        return redirect()->route('adminstock')->with('success', 'Producto creado.');
    }

    /**
     * Guardar fila completa de productos desde la tabla (nombre, categoría, precio, stock, mín., activo).
     * Si cambia stock_actual se registra movimiento tipo ajuste.
     *
     * @param  array<int|string, array<string, mixed>>  $productos
     */
    public function storeProductosTabla(Request $request)
    {
        $validated = $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.nombre' => 'required|string|max:100',
            'productos.*.stock_categoria_id' => 'required|exists:stock_categorias_productos,id',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'productos.*.stock_actual' => 'required|integer|min:0',
            'productos.*.stock_minimo' => 'required|integer|min:0',
        ]);

        $rows = $validated['productos'];
        $user = Auth::user()?->email;
        $ip = $request->ip();

        $cambios = DB::transaction(function () use ($rows, $user, $ip, $request) {
            $count = 0;

            foreach ($rows as $rawKey => $row) {
                $key = (string) $rawKey;

                if (strpos($key, 'new_') === 0) {
                    $activo = $request->boolean('productos.'.$key.'.activo');

                    $producto = StockProducto::query()->create([
                        'nombre' => (string) $row['nombre'],
                        'descripcion' => null,
                        'stock_categoria_id' => (int) $row['stock_categoria_id'],
                        'precio_unitario' => round((float) $row['precio_unitario'], 2),
                        'stock_actual' => (int) $row['stock_actual'],
                        'stock_minimo' => (int) $row['stock_minimo'],
                        'activo' => $activo,
                    ]);

                    $stockInicial = (int) $row['stock_actual'];
                    if ($stockInicial > 0) {
                        StockMovimientoStock::query()->create([
                            'stock_producto_id' => $producto->id,
                            'tipo_movimiento' => 'entrada',
                            'cantidad' => $stockInicial,
                            'cantidad_anterior' => 0,
                            'cantidad_nueva' => $stockInicial,
                            'motivo' => 'Stock inicial al crear producto (tabla)',
                            'usuario_responsable' => $user,
                            'created_at' => now(),
                        ]);
                    }

                    StockAuditoria::registrar(
                        'stock_productos',
                        (int) $producto->id,
                        'INSERT',
                        $user,
                        null,
                        $producto->toArray(),
                        $ip
                    );
                    $count++;

                    continue;
                }

                $productoId = (int) $key;
                if ($productoId < 1) {
                    continue;
                }

                /** @var StockProducto|null $producto */
                $producto = StockProducto::query()->lockForUpdate()->find($productoId);
                if (! $producto) {
                    continue;
                }

                $activo = $request->boolean('productos.'.$productoId.'.activo');
                $nuevoStock = (int) $row['stock_actual'];
                $anteriorStock = (int) $producto->stock_actual;
                $stockCambio = $nuevoStock !== $anteriorStock;

                $antes = $producto->toArray();

                $producto->nombre = (string) $row['nombre'];
                $producto->stock_categoria_id = (int) $row['stock_categoria_id'];
                $producto->precio_unitario = round((float) $row['precio_unitario'], 2);
                $producto->stock_minimo = (int) $row['stock_minimo'];
                $producto->activo = $activo;

                if ($stockCambio) {
                    $producto->stock_actual = $nuevoStock;
                }

                if (! $producto->isDirty()) {
                    continue;
                }

                $producto->save();
                $count++;

                StockAuditoria::registrar(
                    'stock_productos',
                    (int) $producto->id,
                    'UPDATE',
                    $user,
                    $antes,
                    $producto->fresh()->toArray(),
                    $ip
                );

                if ($stockCambio) {
                    $delta = $nuevoStock - $anteriorStock;
                    StockMovimientoStock::query()->create([
                        'stock_producto_id' => $producto->id,
                        'tipo_movimiento' => 'ajuste',
                        'cantidad' => abs($delta),
                        'cantidad_anterior' => $anteriorStock,
                        'cantidad_nueva' => $nuevoStock,
                        'motivo' => 'Ajuste desde tabla productos'.($delta >= 0 ? ' (+'.$delta.')' : ' ('.$delta.')'),
                        'usuario_responsable' => $user,
                        'created_at' => now(),
                    ]);
                }
            }

            return $count;
        });

        if ($cambios === 0) {
            return redirect()->route('adminstock')->with('error', 'No hubo cambios en los productos.');
        }

        return redirect()->route('adminstock')->with('success', 'Productos guardados correctamente.');
    }

    /**
     * Actualización masiva: por producto, entrada de stock (suma).
     *
     * @param  array<string, array{cantidad_ingresar?: int|string|null}>  $lineas
     */
    public function storeActualizacionMasiva(Request $request)
    {
        $validated = $request->validate([
            'lineas' => 'required|array',
            'lineas.*.cantidad_ingresar' => 'nullable|integer|min:0',
        ]);

        $lineas = $validated['lineas'];
        $user = Auth::user()?->email;

        $cambios = DB::transaction(function () use ($lineas, $user) {
            $count = 0;

            foreach ($lineas as $productoId => $row) {
                $productoId = (int) $productoId;
                if ($productoId < 1) {
                    continue;
                }

                /** @var StockProducto|null $producto */
                $producto = StockProducto::query()->lockForUpdate()->find($productoId);
                if (! $producto) {
                    continue;
                }

                $cant = isset($row['cantidad_ingresar']) && $row['cantidad_ingresar'] !== '' && $row['cantidad_ingresar'] !== null
                    ? (int) $row['cantidad_ingresar']
                    : 0;

                if ($cant > 0) {
                    $anterior = $producto->stock_actual;
                    $nueva = $anterior + $cant;
                    $producto->stock_actual = $nueva;
                    $producto->save();
                    $count++;

                    StockMovimientoStock::query()->create([
                        'stock_producto_id' => $producto->id,
                        'tipo_movimiento' => 'entrada',
                        'cantidad' => $cant,
                        'cantidad_anterior' => $anterior,
                        'cantidad_nueva' => $nueva,
                        'motivo' => 'Actualización masiva de stock',
                        'usuario_responsable' => $user,
                        'created_at' => now(),
                    ]);
                }
            }

            return $count;
        });

        if ($cambios === 0) {
            return redirect()->route('adminstock')->with('error', 'No hubo cambios: ingresá cantidad a agregar en al menos un producto.');
        }

        return redirect()->route('adminstock')->with('success', 'Stock actualizado correctamente.');
    }
}
