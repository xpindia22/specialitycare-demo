<?php
namespace App\Http\Controllers;

use App\Models\Allergen;
use Illuminate\Http\Request;

class AllergenController extends Controller
{
    public function index()
    {
        $allergens = Allergen::orderBy('category')->orderBy('sort_order')->orderBy('name')->get()->groupBy('category');
        $categories = Allergen::CATEGORIES;
        return view('settings.allergens', compact('allergens', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100|unique:allergens,name',
            'category' => 'required|in:drugs,foods,environmental,other',
        ]);
        Allergen::create(['name' => ucwords(trim($data['name'])), 'category' => $data['category'], 'is_active' => true]);
        return back()->with('success', 'Allergen "'.ucwords(trim($data['name'])).'" added.');
    }

    public function destroy(Allergen $allergen)
    {
        $name = $allergen->name;
        $allergen->delete();
        return back()->with('success', '"'.$name.'" removed from the list.');
    }
}
