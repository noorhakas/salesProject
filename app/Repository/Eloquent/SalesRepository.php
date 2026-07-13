<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\SalesInterface;
use App\Models\Sale;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Account;
use App\Http\Resources\API\UserSalesResource;

class SalesRepository implements SalesInterface
{
   

    public function storeUserSales($request)
        {
            \DB::beginTransaction();

            try {
                foreach ($request->product_list as $item) {
                    $product = Product::find($item['product_id']);
                    if (!$product) {
                        \Log::warning("Product not found: " . $item['product_id']);
                        continue;
                    }

                    $product_price = $product->price;
                    Sale::updateOrCreate(
                        [
                            'product_id' => $item['product_id'],
                            'account_id' => $request->account_id,
                            'user_id'    => auth()->id(),
                            'month_date' => Carbon::now()->format('Y-m-01'),
                        ],
                        [
                            'unit'        => $item['unit'],
                            'price'       => $product_price,
                            'total_price' => $item['unit'] * $product_price,
                        ]
                    );
                }

                \DB::commit();
                return ['status' => true, 'message' => 'Sales recorded successfully.'];

            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error("Sale insert error: " . $e->getMessage());
                return ['status' => false, 'message' => trans('messages.server_error')];
            }
        }


    /* Product::select('products.id', 'products.name','products.price',
            \DB::raw('COALESCE(sales.unit, 0) as unit'),
             \DB::raw('COALESCE(sales.price, products.price) as sale_price'),
              \DB::raw('COALESCE(sales.total_price, 0) as total_price')) 
              */
    public function getUserProductsWithSales($accountId)
    {
        $userId = auth()->id();
        $now = Carbon::now();
        $limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;

        $products = Product::select('products.id', 'products.name','products.price',
            \DB::raw('COALESCE(sales.unit, 0) as unit'))
            ->whereIn('products.id', auth()->user()->products->pluck('id')) // filter by user's assigned products
            ->leftJoin('sales', function ($join) use ($userId, $accountId, $now) {
                $join->on('products.id', '=', 'sales.product_id')
                        ->where('sales.user_id', $userId)
                        ->where('sales.account_id', $accountId)
                        ->whereMonth('sales.month_date', $now->month)
                        ->whereYear('sales.month_date', $now->year);
            })->groupBy('products.id', 'products.name', 'products.price', 'sales.unit')
            ->paginate($limit);

            // Calculate totals for the current month
            $totals = Sale::selectRaw('SUM(unit) as total_units, SUM(total_price) as total_price')
                ->where('user_id', $userId)->where('account_id', $accountId)
                ->whereMonth('month_date', $now->month)->whereYear('month_date', $now->year)
                ->first();

            return [
                'status' => true,
                'message' => 'Sales for the current month loaded successfully.',
                'data' => [
                    'total_units' => $totals->total_units ?? 0,
                    'total_price' => $totals->total_price ?? 0,
                    'products' => UserSalesResource::collection($products),
                ],
            ];
        }


   public function getProductsWithAccountsSales($request)
{
    $userId = auth()->id();
    $now = now();

    // Get per-page and current page values
    $productsPerPage = $request->input('products_per_page', 20);
    $accountsPerPage = $request->input('accounts_per_page', 20);
    $productsPage = $request->input('products_page', 1);
    $accountsPage = $request->input('accounts_page', 1);

    // Get total counts
    $productsTotal = \App\Models\Product::count();
    $accountsTotal = \App\Models\Account::count();

    // Fetch paginated products and accounts using forPage()
    $products = \App\Models\Product::forPage($productsPage, $productsPerPage)->get();
    $accounts = \App\Models\Account::forPage($accountsPage, $accountsPerPage)->get();

    // IDs
    $productIds = $products->pluck('id')->toArray();
    $accountIds = $accounts->pluck('id')->toArray();

    // Fetch sales
    $sales = \App\Models\Sale::where('user_id', $userId)
        ->whereIn('product_id', $productIds)
        ->whereIn('account_id', $accountIds)
        ->whereMonth('month_date', $now->month)
        ->whereYear('month_date', $now->year)
        ->get()
        ->groupBy(fn($s) => $s->product_id . '_' . $s->account_id);

    // Build product list
    $productList = $products->map(function ($product) use ($accounts, $sales) {
        $accountData = $accounts->map(function ($account) use ($product, $sales) {
            $key = $product->id . '_' . $account->id;
            $sale = $sales[$key][0] ?? null;

            return [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'unit' => $sale?->unit ?? 0,
                'price' => $sale?->price ?? $product->price,
                'total_price' => ($sale?->unit ?? 0) * ($sale?->price ?? $product->price),
            ];
        });

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'default_price' => $product->price,
            'accounts' => $accountData,
        ];
    });

    return [
        'status' => true,
        'message' => 'Products and accounts with sales data loaded.',
        'data' => $productList,
        'pagination' => [
            'products' => [
                'current_page' => $productsPage,
                'per_page' => $productsPerPage,
                'total' => $productsTotal,
                'last_page' => ceil($productsTotal / $productsPerPage),
            ],
            'accounts' => [
                'current_page' => $accountsPage,
                'per_page' => $accountsPerPage,
                'total' => $accountsTotal,
                'last_page' => ceil($accountsTotal / $accountsPerPage),
            ],
        ],
    ];
}



}