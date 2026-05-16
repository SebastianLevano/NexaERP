<?php

namespace App\Http\Controllers\App;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $activeStatuses = [SaleStatus::Confirmed, SaleStatus::Paid];

        // KPIs
        $todayAgg = Sale::query()
            ->whereIn('status', $activeStatuses)
            ->whereDate('issued_at', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as sum')
            ->first();

        $monthAgg = Sale::query()
            ->whereIn('status', $activeStatuses)
            ->where('issued_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as sum')
            ->first();

        $avgTicket = ($monthAgg->count ?? 0) > 0
            ? round(((float) $monthAgg->sum) / (int) $monthAgg->count, 2)
            : 0;

        $belowMinimum = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<', 'min_stock')
            ->count();

        // Serie diaria últimos 30 días
        $series = $this->dailySalesSeries(days: 30, statuses: $activeStatuses);

        // Top 5 productos del mes
        $topProducts = SaleItem::query()
            ->whereHas('sale', fn ($q) => $q
                ->whereIn('status', $activeStatuses)
                ->where('issued_at', '>=', $monthStart))
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->selectRaw('products.id, products.name, products.sku, SUM(sale_items.quantity) as total_qty, SUM(sale_items.line_total) as total_amount')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'quantity' => (int) $row->total_qty,
                'amount' => (float) $row->total_amount,
            ]);

        // Últimas 5 ventas
        $recentSales = Sale::query()
            ->with(['customer:id,name'])
            ->latest('issued_at')
            ->limit(5)
            ->get()
            ->map(fn (Sale $s) => [
                'id' => $s->id,
                'number' => $s->number,
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
                'total' => (float) $s->total,
                'issued_at' => $s->issued_at?->toIso8601String(),
                'customer' => $s->customer?->name,
            ]);

        return Inertia::render('Dashboard', [
            'kpis' => [
                'today_count' => (int) ($todayAgg->count ?? 0),
                'today_sum' => (float) ($todayAgg->sum ?? 0),
                'month_count' => (int) ($monthAgg->count ?? 0),
                'month_sum' => (float) ($monthAgg->sum ?? 0),
                'avg_ticket' => $avgTicket,
                'below_minimum' => $belowMinimum,
            ],
            'series' => $series,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
        ]);
    }

    /**
     * @param  array<SaleStatus>  $statuses
     * @return array{labels: array<string>, values: array<float>}
     */
    private function dailySalesSeries(int $days, array $statuses): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $rows = Sale::query()
            ->whereIn('status', $statuses)
            ->whereBetween('issued_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(issued_at) as d, COALESCE(SUM(total),0) as sum')
            ->groupBy('d')
            ->pluck('sum', 'd');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $start->copy()->addDays($i);
            $key = $d->toDateString();
            $labels[] = $key;
            $values[] = (float) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
