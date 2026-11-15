<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    /**
     * Categorías disponibles para clasificar los gastos del negocio.
     *
     * @var array<int, string>
     */
    public const CATEGORIES = [
        'Insumos',
        'Combustible',
        'Mantenimiento',
        'Renta',
        'Sueldos',
        'Servicios',
        'Marketing',
        'Impuestos',
        'Otros',
    ];

    /**
     * Métodos de pago aceptados para registrar un gasto.
     *
     * @var array<int, string>
     */
    public const PAYMENT_METHODS = ['Efectivo', 'Tarjeta', 'Transferencia', 'Otro'];

    protected $fillable = [
        'concept',
        'category',
        'amount',
        'expense_date',
        'payment_method',
        'supplier',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Usuario administrador que registró el gasto.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Gastos dentro de un rango de fechas (inclusive).
     */
    public function scopeBetween($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);
    }
}
