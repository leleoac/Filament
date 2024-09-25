<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::get();
        return view('category.index', compact('categories'));
    }

    public function create(){
        return view('category.create');
    }
    public function store(Request $request)
    {
        $data =  $request->validate([
            'name'=>'required|max:255|string',
             'description'=>'required|max:255|string',
             'is_active'=>'sometimes',
            ]

        );
        $data['is_active'] =  array_key_exists('is_active', $data) ? true : false;
        Category::create($data);
        return redirect('categories')->with('status', 'Category Created');
    }

    public function edit(int $id)
    {
        $category=Category::findOrFail($id);
        //return $category;
        return view('category.edit',compact('category'));

    }
    public function update(Request $request, $id)
    {
       $request->validate([
                'name'=>'required|max:255|string',
                'description'=>'required|max:255|string',
                'is_active'=>'sometimes',
            ]

        );
        Category::findOrFail($id)->update([
           'name'=>$request->name,
           'description'=>$request->description,
            'is_active' =>  $request->is_active == true ? 1 : 0,

        ]);
        //return redirect()->back()->with('status', 'Category Update');
        return redirect('categories')->with('status', 'Category Updated');
    }

    public function destroy(int $id)
    {
        $category = Category::FindOrFail($id);
        $category->delete();

        return redirect()->back()->with('status', 'Category Deleted');
    }
}
