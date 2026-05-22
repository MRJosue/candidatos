<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        return view('companies.index', [
            'companies' => $request->user()
                ->companies()
                ->withCount(['positions', 'vacancies'])
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create()
    {
        return view('companies.create', [
            'company' => new Company,
        ]);
    }

    public function store(Request $request)
    {
        $company = $request->user()->companies()->create($this->validatedData($request));

        return redirect()->route('companies.show', $company)->with('status', 'Compania creada.');
    }

    public function show(Request $request, Company $company)
    {
        abort_unless($company->recruiter_id === $request->user()->id, 403);

        return view('companies.show', [
            'company' => $company->load([
                'positions' => fn ($query) => $query->latest(),
                'vacancies' => fn ($query) => $query->latest(),
            ]),
        ]);
    }

    public function edit(Request $request, Company $company)
    {
        abort_unless($company->recruiter_id === $request->user()->id, 403);

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        abort_unless($company->recruiter_id === $request->user()->id, 403);

        $company->update($this->validatedData($request, $company));

        return redirect()->route('companies.show', $company)->with('status', 'Compania actualizada.');
    }

    public function destroy(Request $request, Company $company)
    {
        abort_unless($company->recruiter_id === $request->user()->id, 403);

        $company->delete();

        return redirect()->route('companies.index')->with('status', 'Compania eliminada.');
    }

    private function validatedData(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('companies', 'name')
                    ->where('recruiter_id', $request->user()->id)
                    ->ignore($company),
            ],
            'industry' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
