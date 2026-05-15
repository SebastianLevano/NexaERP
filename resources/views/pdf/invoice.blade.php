<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $sale->number }}</title>
    <style>
        @page { margin: 18mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.4;
        }
        h1 { margin: 0; font-size: 16px; letter-spacing: 0.5px; }
        h2 { margin: 0 0 4px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #555; font-weight: 600; }
        .muted { color: #666; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .right { text-align: right; }
        .center { text-align: center; }
        .row { width: 100%; }
        .row td { vertical-align: top; }
        .divider { border-top: 1px solid #ddd; margin: 10px 0; }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.lines th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-bottom: 1px solid #ccc;
            padding: 4px 0;
        }
        table.lines td {
            padding: 5px 0;
            border-bottom: 1px dotted #eee;
        }
        .totals { margin-top: 10px; }
        .totals td { padding: 2px 0; }
        .total-row td {
            padding-top: 6px;
            border-top: 1px solid #999;
            font-size: 12px;
            font-weight: 700;
        }
        .footer {
            margin-top: 18px;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #999;
            border-radius: 3px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="row">
        <tr>
            <td style="width: 60%">
                <h1>{{ $company['name'] }}</h1>
                @if ($company['document'])
                    <div class="muted">{{ $company['document'] }}</div>
                @endif
                @if ($company['address'])
                    <div class="muted">{{ $company['address'] }}</div>
                @endif
                @if ($company['phone'] || $company['email'])
                    <div class="muted">
                        {{ $company['phone'] }}
                        @if ($company['phone'] && $company['email']) · @endif
                        {{ $company['email'] }}
                    </div>
                @endif
            </td>
            <td class="right">
                <h2>Comprobante</h2>
                <div class="mono" style="font-size: 14px; font-weight: 700;">
                    {{ $sale->number }}
                </div>
                <div class="muted" style="margin-top: 3px;">
                    {{ $sale->issued_at?->format('d/m/Y H:i') }}
                </div>
                <div style="margin-top: 6px;">
                    <span class="status-badge">{{ $sale->status->label() }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Cliente -->
    @if ($sale->customer)
        <table class="row">
            <tr>
                <td style="width: 60%">
                    <h2>Cliente</h2>
                    <div style="font-weight: 600;">{{ $sale->customer->name }}</div>
                    <div class="muted mono">
                        {{ $sale->customer->document_type?->shortLabel() }} {{ $sale->customer->document_number }}
                    </div>
                    @if ($sale->customer->address)
                        <div class="muted">{{ $sale->customer->address }}</div>
                    @endif
                </td>
                <td class="right" style="width: 40%">
                    @if ($sale->seller)
                        <h2>Atendido por</h2>
                        <div>{{ $sale->seller->name }}</div>
                    @endif
                </td>
            </tr>
        </table>
        <div class="divider"></div>
    @endif

    <!-- Items -->
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 50%">Producto</th>
                <th class="right" style="width: 12%">Cant.</th>
                <th class="right" style="width: 18%">P. Unit</th>
                <th class="right" style="width: 20%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>
                        <div>{{ $item->product?->name }}</div>
                        <div class="muted mono" style="font-size: 9px;">{{ $item->product?->sku }}</div>
                    </td>
                    <td class="right mono">{{ $item->quantity }}</td>
                    <td class="right mono">S/ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="right mono">S/ {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <table class="row totals">
        <tr>
            <td style="width: 60%"></td>
            <td>
                <table style="width: 100%">
                    <tr>
                        <td class="muted">Subtotal</td>
                        <td class="right mono">S/ {{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    @if ($sale->tax > 0)
                        <tr>
                            <td class="muted">IGV</td>
                            <td class="right mono">S/ {{ number_format($sale->tax, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total</td>
                        <td class="right mono">S/ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Pagos -->
    @if ($sale->payments->count())
        <div class="divider"></div>
        <h2>Pagos</h2>
        <table class="lines">
            <tbody>
                @foreach ($sale->payments as $payment)
                    <tr>
                        <td style="width: 25%">{{ $payment->method->label() }}</td>
                        <td class="muted" style="width: 35%">{{ $payment->reference ?: '—' }}</td>
                        <td class="muted">{{ $payment->paid_at?->format('d/m/Y H:i') }}</td>
                        <td class="right mono">S/ {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
                @if ($sale->balance() > 0)
                    <tr>
                        <td colspan="3" class="right" style="border-top: 1px solid #ccc; padding-top: 6px;">
                            <strong>Saldo pendiente</strong>
                        </td>
                        <td class="right mono" style="border-top: 1px solid #ccc; padding-top: 6px; font-weight: 700;">
                            S/ {{ number_format($sale->balance(), 2) }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="footer">
        Gracias por su compra · {{ $company['name'] }}
    </div>
</body>
</html>
