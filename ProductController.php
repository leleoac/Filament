<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{

    public static function getToken ()
    {

        $data = [
            'username' => 'kminchelle',
            'password' => '0lelplR'
        ];

        $headers = [
            'Content-Type' => 'application/json',
        ];

//        $response = Http::get('http://example.com');
        $response = Http::withHeaders($headers)->post('https://dummyjson.com/auth/login', $data);
        $resultObj = json_decode($response->body());
        $resultArr = json_decode($response->body(), true);
        return $resultObj->token;

    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();

        return view('product.index', compact('products'));    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product.create');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
                'title'=>'required|max:255|string',
                'description'=>'required|max:255|string',
                'price' => 'required|numeric',
                'discountPercentage' => 'required|numeric|between:0,100',
                'rating' => 'required|numeric|between:0,5',
                'stock' => 'required|integer|min:0',
                'brand' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                //'thumbnail' => 'required|string|max:255',

            ]
        );
        $data['rating'] = Product::getRatingAttribute($request->input('rating'));
        $data['thumbnail'] = $request->input('thumbnail') ?? 'null';
        $data['images'] = $request->input('images') ?? [];

        Product::createApi($data);
        Product::create($data);
        return redirect('products/create')->with('status', 'Product Created');
    }

    /**
     * Display the specified resource.
     */
    public static function show(string $id)
    {
        $product = Product::findOrFail($id);

        // Supondo que 'images' é uma string separada por espaços ou outra forma
        if (is_string($product->images)) {
            $product->images = explode("\n", $product->images);
        }
        return view('product.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product=Product::findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        return view('product.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {


        $request->validate([
                'title'=>'required|max:255|string',
                'description'=>'required|max:255|string',
                'price' => 'required|numeric',
                'discountPercentage' => 'required|numeric|between:0,100',
                'rating' => 'required|numeric|between:0,5',
                'stock' => 'required|integer|min:0',
                'brand' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'thumbnail' => 'required|string|max:255',
                'images' => 'nullable|string',
            ]

        );
        $images = explode("\n", $request->input('images'));
        $images = array_filter(array_map('trim', $images));
        $data['images'] = json_encode($images);
        //$category = Category::firstOrCreate(['name' => $request->category], ['description' => 'Generated upon product update', 'is_active' => true]);


        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'discountPercentage' => $request->discountPercentage,
            'rating' => Product::getRatingAttribute($request->rating),
            'stock' => $request->stock,
            'brand' => $request->brand,
            'category_id' => $request->category_id,
            'thumbnail' => $request->thumbnail,
            'images' => $images ?? [],
        ];


            $response = Product::updateApi($data, $id);
            Product::findOrFail($id)->update($data);
            return redirect('products')->with('status', 'Product Updated');


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        //Product::destroyApi($id);
        return redirect('products')->with('status', 'Product Deleted');
    }

    public function search(Request $request)
    {
        $query = $request->input('query'); // Assume que a busca é passada como um parâmetro de query 'query'
        $products = Product::where('title','like',"%$query%")
                            ->orWhere('description', 'like', "%$query%")
                            ->orWhere('brand', 'like', "%$query%")
                            //->orWhere('category', 'like', "%$query%")
                            ->get();

        return view('product.search', compact('products', 'query'));
    }


}
