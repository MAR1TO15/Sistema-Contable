<?php

namespace App\Http\Controllers;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Services\ActiveClient;
use App\Services\BaseAccountCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Chart of accounts of the active client. The {account} route binding
 * only resolves accounts of that client (see AppServiceProvider).
 */
class AccountController extends Controller
{
    public function __construct(private ActiveClient $activeClient) {}

    public function index(): View
    {
        $accounts = $this->activeClient->get()->accounts()->orderBy('code')->get();

        return view('accounts.index', [
            'accountsByParent' => $accounts->groupBy(fn (Account $account) => $account->parent_id ?? 0),
            'hasAccounts' => $accounts->isNotEmpty(),
        ]);
    }

    public function create(): View
    {
        $groups = $this->activeClient->get()->accounts()
            ->where('is_postable', false)
            ->orderBy('code')
            ->get();

        return view('accounts.create', ['groups' => $groups]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $client = $this->activeClient->get();
        $parent = $request->filled('parent_id') ? $client->accounts()->find($request->validated('parent_id')) : null;

        // A child account always belongs to the same type as its parent.
        $type = $parent?->type ?? AccountType::from($request->validated('type'));
        $nature = $request->filled('nature')
            ? AccountNature::from($request->validated('nature'))
            : ($parent?->nature ?? $type->defaultNature());

        $client->accounts()->create([
            'parent_id' => $parent?->id,
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'type' => $type,
            'nature' => $nature,
            'is_postable' => $request->boolean('is_postable'),
        ]);

        return redirect()->route('accounts.index');
    }

    public function edit(Account $account): View
    {
        return view('accounts.edit', [
            'account' => $account,
            'hasChildren' => $account->children()->exists(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update([
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'nature' => $request->validated('nature') ?? $account->nature,
            'is_postable' => $request->boolean('is_postable'),
        ]);

        return redirect()->route('accounts.index');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->children()->exists()) {
            return redirect()->route('accounts.index')->withErrors([
                'account' => "La cuenta {$account->code} tiene subcuentas y no se puede eliminar.",
            ]);
        }

        $account->delete();

        return redirect()->route('accounts.index');
    }

    public function loadBaseCatalog(BaseAccountCatalog $catalog): RedirectResponse
    {
        $client = $this->activeClient->get();

        if ($client->accounts()->exists()) {
            return redirect()->route('accounts.index')->withErrors([
                'account' => 'El catálogo base solo se puede cargar en una empresa sin cuentas.',
            ]);
        }

        $catalog->createFor($client);

        return redirect()->route('accounts.index');
    }
}
