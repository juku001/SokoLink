<?php

namespace App\Http\Controllers;


use App\Models\CartItem;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Border;






class ExportFileReportController extends Controller
{





    /**
     * Export full seller report as an Excel file.
     *
     * @OA\Get(
     *     path="/reports/export/excel",
     *     tags={"Reports"},
     *     summary="Export seller data to Excel",
     *     description="Generates and downloads an Excel workbook containing sales, credit score, and online performance.",
     *     operationId="exportSellerReport",
     *     @OA\Response(
     *         response=200,
     *         description="Excel file download",
     *     ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function csv()
    {
        $spreadsheet = new Spreadsheet();

        $this->salesReport($spreadsheet);
        $this->creditScore($spreadsheet);
        $this->onlinePerformance($spreadsheet);
        $response = $this->download($spreadsheet);

        return $response;
    }

    /**
     * @OA\Get(
     *     path="/reports/export/pdf",
     *     tags={"Reports"},
     *     summary="Export seller data to PDF",
     *     description="Generates and downloads a PDF report with sales, credit score, and online performance.",
     *     operationId="exportSellerReportPdf",
     *     @OA\Response(
     *         response=200,
     *         description="PDF file download",
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=403, ref="#/components/responses/403")
     * )
     */

    public function pdf()
    {
        $spreadsheet = new Spreadsheet();

        $this->salesReport($spreadsheet);
        $this->creditScore($spreadsheet);
        $this->onlinePerformance($spreadsheet);
        $response = $this->downloadPdf($spreadsheet);

        return $response;
    }





    private function salesReport($spreadSheet)
    {
        $sheet = $spreadSheet->getActiveSheet();
        $sheet->setTitle('Sales Report');

        // Title
        $now = Carbon::now();
        $sheet->setCellValue('A1', $now->format('F Y') . ' Sales Report'); // e.g. "September 2025 Sales Report"
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Week headers
        $sheet->setCellValue('B2', 'Week 1');
        $sheet->setCellValue('C2', 'Week 2');
        $sheet->setCellValue('D2', 'Week 3');
        $sheet->setCellValue('E2', 'Week 4');

        foreach (range('B', 'E') as $col) {
            $sheet->getStyle($col . '2')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Row labels
        $sheet->setCellValue('A3', 'Sales');
        $sheet->setCellValue('A4', 'Expenses');
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);

        // Month bounds
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $salesData = [];
        $expensesData = [];

        for ($week = 1; $week <= 4; $week++) {
            $weekStart = $startOfMonth->copy()->addDays(($week - 1) * 7)->startOfDay();


            if ($weekStart->gt($endOfMonth)) {
                $salesData[$week] = 0;
                $expensesData[$week] = 0;
                continue;
            }

            $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
            if ($weekEnd->gt($endOfMonth)) {
                $weekEnd = $endOfMonth->copy()->endOfDay();
            }
            // $authId = auth()->user()->id;
            $authId = 2;
            $salesData[$week] = Sale::where('seller_id', $authId)->whereBetween('created_at', [$weekStart->toDateTimeString(), $weekEnd->toDateTimeString()])
                ->sum('amount');

            $expensesData[$week] = Expense::where('seller_id', $authId)->whereBetween('created_at', [$weekStart->toDateTimeString(), $weekEnd->toDateTimeString()])
                ->sum('amount');
        }

        foreach (range(1, 4) as $week) {
            $col = chr(65 + $week);
            $sheet->setCellValue($col . '3', $salesData[$week]);
            $sheet->setCellValue($col . '4', $expensesData[$week]);
        }
        $sheet->getStyle('A2:E4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B3:E4')->getNumberFormat()->setFormatCode('#,##0.00');
    }




    private function creditScore(Spreadsheet $spreadsheet)
    {
        $sellerId = auth()->id();
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfMonth();

        $months = [];
        $cursor = $startOfYear->copy();

        while ($cursor <= $endOfYear) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $totalRevenue = DB::table('sales')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $totalOrders = DB::table('sales')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $refunds = DB::table('sales')
                ->where('seller_id', $sellerId)
                ->where('status', 'refunded')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $lateShipments = DB::table('shipments')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('delivered_late', true)
                ->count();

            $revenueScore = min($totalRevenue / 100000, 1) * 40;
            $orderScore = min($totalOrders / 500, 1) * 30;
            $refundPenalty = min($refunds * 5, 20);
            $latePenalty = min($lateShipments * 2, 10);

            $score = max(0, round($revenueScore + $orderScore - $refundPenalty - $latePenalty));

            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'month_abbr' => $monthStart->format('M'),
                'credit_score' => $score,
                'total_revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'refunds' => $refunds,
                'late_shipments' => $lateShipments,
            ];

            $cursor->addMonth();
        }
        $data = $months;




        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Credit Score');

        // Header row
        $headers = ['Month', 'Credit Score', 'Revenue', 'Orders', 'Refunds', 'Late Shipments'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data rows
        $row = 2;
        foreach ($data as $m) {
            $sheet->fromArray([
                $m['month_abbr'],
                $m['credit_score'],
                $m['total_revenue'],
                $m['orders'],
                $m['refunds'],
                $m['late_shipments']
            ], null, 'A' . $row);
            $row++;
        }

        // Simple borders & auto-width
        $sheet->getStyle("A1:F" . ($row - 1))
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ----- Optional: Line chart of Credit Score over months -----
        $lastRow = $row - 1;
        $categories = [new DataSeriesValues('String', "Credit Score!A2:A{$lastRow}", null, $lastRow - 1)];
        $values = [new DataSeriesValues('Number', "Credit Score!B2:B{$lastRow}", null, $lastRow - 1, [], null, '00f2ae')];

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            [],
            $categories,
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('Monthly Credit Score');

        $chart = new Chart(
            'credit_chart',
            $title,
            $legend,
            $plotArea,
            true,
            0,
            null,
            null
        );

        // Position the chart
        $chart->setTopLeftPosition('H2');
        $chart->setBottomRightPosition('P20');

        $sheet->addChart($chart);
    }



    private function onlinePerformance(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Online Performance');

        // ----- Title -----
        $now = Carbon::now();
        $sheet->setCellValue('A1', $now->format('F Y') . ' Online Performance');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // ----- Query data (your existing logic) -----
        $sellerId = auth()->id();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $visitsCurrent = DB::table('store_visits')
            ->where('user_id', $sellerId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $visitsPrevious = DB::table('store_visits')
            ->where('user_id', $sellerId)
            ->whereBetween('created_at', [
                $startOfMonth->copy()->subMonth(),
                $endOfMonth->copy()->subMonth()
            ])
            ->count();

        $cartAdditions = CartItem::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereHas('product.store', fn($q) => $q->where('seller_id', $sellerId))
            ->count();


        $orders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereHas('items.product.store', fn($q) => $q->where('seller_id', $sellerId))
            ->count();

        $abandonedCarts = DB::table('carts')
            ->join('cart_items', 'carts.id', '=', 'cart_items.cart_id')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->join('stores', 'products.store_id', '=', 'stores.id')
            ->where('stores.seller_id', $sellerId)
            ->whereBetween('carts.created_at', [$startOfMonth, $endOfMonth])
            ->whereRaw('NOT EXISTS (
        SELECT 1
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE oi.product_id = cart_items.product_id
          AND o.created_at >= carts.created_at
    )')
            ->distinct('carts.id')
            ->count();


        $percentChange = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 1);
        };

        $data = [
            ['Metric', 'Value', 'Change %', 'Status'],
            [
                'Visits',
                $visitsCurrent,
                $percentChange($visitsCurrent, $visitsPrevious),
                $visitsCurrent >= $visitsPrevious ? 'Positive' : 'Negative'
            ],
            ['Cart Additions', $cartAdditions, 0, 'Positive'],
            ['Conversion Rate %', $visitsCurrent ? round(($orders / $visitsCurrent) * 100, 2) : 0, 0, 'Neutral'],
            ['Cart Abandonment %', $cartAdditions ? round(($abandonedCarts / $cartAdditions) * 100, 2) : 0, 0, 'Neutral'],
        ];

        // ----- Write table -----
        $sheet->fromArray($data, null, 'A3');
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);
        $sheet->getStyle('A3:D' . (count($data) + 2))
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ----- Add a bar chart comparing the metrics -----
        $lastRow = 3 + count($data) - 2; // start at row 4 to skip header

        $categories = [
            new DataSeriesValues('String', "Online Performance!A4:A{$lastRow}", null, $lastRow - 3)
        ];
        $values = [
            new DataSeriesValues('Number', "Online Performance!B4:B{$lastRow}", null, $lastRow - 3, [], null, 'ff0088')
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            [],
            $categories,
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('Online Performance Metrics');

        $chart = new Chart(
            'online_chart',
            $title,
            $legend,
            $plotArea,
            true,
            0,
            null,
            null
        );

        // Place the chart somewhere below the table
        $chart->setTopLeftPosition('F3');
        $chart->setBottomRightPosition('N20');

        $sheet->addChart($chart);
    }


    private function download($spreadsheet)
    {
        $writer = new Xlsx($spreadsheet);
        $fileName = 'sokolink_report_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $disposition = $response->headers->makeDisposition(
            \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }










    private function downloadPdf($spreadsheet)
    {
        // Set the PDF renderer
        $writer = new Dompdf($spreadsheet);
        $fileName = 'sokolink_report_' . now()->format('Ymd_His') . '.pdf';

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $disposition = $response->headers->makeDisposition(
            \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

}
