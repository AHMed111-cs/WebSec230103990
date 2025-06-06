<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use DB;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductsController extends Controller {

	use ValidatesRequests;

	public function __construct()
    {
       //broken access control
		// $this->middleware('auth:web')->except('list');
    }

	public function list(Request $request) {

		$query = Product::select("products.*");

		$query->when($request->keywords, 
		fn($q)=> $q->where("name", "like", "%$request->keywords%"));

		$query->when($request->min_price, 
		fn($q)=> $q->where("price", ">=", $request->min_price));
		
		$query->when($request->max_price, fn($q)=> 
		$q->where("price", "<=", $request->max_price));
		
		$query->when($request->order_by, 
		fn($q)=> $q->orderBy($request->order_by, $request->order_direction??"ASC"));

		$products = $query->get();

		return view('products.list', compact('products'));
	}

	public function edit(Request $request, Product $product = null) {
//broken access control
		//if(!auth()->user()) return redirect('/');

		$product = $product??new Product();

		return view('products.edit', compact('product'));
	}

	public function save(Request $request, Product $product = null) {

		$this->validate($request, [
	        'code' => ['required', 'string', 'max:32'],
	        'name' => ['required', 'string', 'max:128'],
	        'model' => ['required', 'string', 'max:256'],
	        'description' => ['required', 'string', 'max:1024'],
	        'price' => ['required', 'numeric'],
	    ]);

		$product = $product??new Product();
		$product->fill($request->all());
		$product->save();

		return redirect()->route('products_list');
	}

	public function delete(Request $request, Product $product) {

		if(!auth()->user()->hasPermissionTo('delete_products')) abort(401);

		$product->delete();

		return redirect()->route('products_list');
	}
	public function purchase(Request $request, Product $product) {
		
		if (!auth()->user()) {
			return redirect('/');
		}
	
		
		$user = auth()->user();
		if ($user->credit < $product->price) {
			return redirect()->back()->withErrors(['error' => 'رصيدك غير كافٍ لشراء هذا المنتج.']);
		}
	
		
		if ($product->stock <= 0) {
			return redirect()->back()->withErrors(['error' => 'المنتج غير متوفر في المخزون.']);
		}
	
		
		$user->credit -= $product->price;
		$user->save();
	
		
		$product->stock -= 1;
		$product->save();
	
		
	
		return redirect()->route('products_list')->with('success', 'تم شراء المنتج بنجاح!');
	}
} 

