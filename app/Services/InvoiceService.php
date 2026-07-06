<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generate(Order $order): Invoice
    {
        $invoice = $order->relationLoaded('invoice') ? $order->invoice : Invoice::where('order_id', $order->id)->first();

        if (!$invoice) {
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'mlm_user_id' => $order->user_id,
                'invoice_date' => now(),
                'total_amount' => $order->total_amount,
                'total_cc' => $order->total_cc_points,
                'status' => 'GENERATED',
            ]);
        }

        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'order' => $order->load('items.product', 'user'),
        ]);

        $filename = $invoice->invoice_number . '.pdf';
        $path = "invoices/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);

        return $invoice;
    }

    public function download(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            $this->generate($invoice->order);
        }

        return Storage::disk('public')->download(
            $invoice->pdf_path,
            $invoice->invoice_number . '.pdf'
        );
    }

    public function stream(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            $this->generate($invoice->order);
        }

        return Storage::disk('public')->response($invoice->pdf_path);
    }
}
