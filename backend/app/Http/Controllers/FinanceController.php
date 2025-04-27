<?php

namespace App\Http\Controllers;

use App\Models\FinanceCategory;
use App\Models\FinanceProject;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * Get all categories
     */
    public function getCategories()
    {
        $categories = FinanceCategory::where('user_id', Auth::id())
                        ->orWhere('is_system', true)
                        ->orderBy('name')
                        ->get();
        
        return response()->json($categories);
    }
    
    /**
     * Create a new category
     */
    public function createCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:30',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:30',
        ]);
        
        $category = FinanceCategory::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'color' => $request->color,
            'type' => $request->type,
            'icon' => $request->icon ?? 'category',
            'is_system' => false,
        ]);
        
        return response()->json($category, 201);
    }
    
    /**
     * Update a category
     */
    public function updateCategory(Request $request, FinanceCategory $category)
    {
        // Only non-system categories owned by user can be updated
        if ($category->is_system || $category->user_id !== Auth::id()) {
            return response()->json(['message' => 'Cannot modify this category'], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:30',
            'icon' => 'nullable|string|max:30',
        ]);
        
        $category->update([
            'name' => $request->name,
            'color' => $request->color,
            'icon' => $request->icon,
        ]);
        
        return response()->json($category);
    }
    
    /**
     * Delete a category
     */
    public function deleteCategory(FinanceCategory $category)
    {
        // Only non-system categories owned by user can be deleted
        if ($category->is_system || $category->user_id !== Auth::id()) {
            return response()->json(['message' => 'Cannot delete this category'], 403);
        }
        
        // Check if category is in use
        $inUse = Transaction::where('finance_category_id', $category->id)->exists();
        if ($inUse) {
            return response()->json(['message' => 'Category is in use by transactions and cannot be deleted'], 422);
        }
        
        $category->delete();
        
        return response()->json(['message' => 'Category deleted successfully']);
    }
    
    /**
     * Get all projects
     */
    public function getProjects()
    {
        $projects = FinanceProject::where('user_id', Auth::id())
                     ->orderBy('name')
                     ->get();
                     
        return response()->json($projects);
    }
    
    /**
     * Get a specific project
     */
    public function getProject(FinanceProject $project)
    {
        // Check if the user owns the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($project);
    }
    
    /**
     * Create a new project
     */
    public function createProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $project = FinanceProject::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
        ]);
        
        return response()->json($project, 201);
    }
    
    /**
     * Update a project
     */
    public function updateProject(Request $request, FinanceProject $project)
    {
        // Check if the user owns the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $project->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        
        return response()->json($project);
    }
    
    /**
     * Delete a project
     */
    public function deleteProject(FinanceProject $project)
    {
        // Check if the user owns the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Remove project association from transactions
        Transaction::where('finance_project_id', $project->id)
                   ->update(['finance_project_id' => null]);
        
        $project->delete();
        
        return response()->json(['message' => 'Project deleted successfully']);
    }
    
    /**
     * Get transactions with pagination
     */
    public function getTransactions(Request $request)
    {
        $query = Transaction::with(['category', 'project'])
                  ->where('user_id', Auth::id());
        
        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }
        
        if ($request->filled('project_id')) {
            $query->where('finance_project_id', $request->project_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('category_id')) {
            $query->where('finance_category_id', $request->category_id);
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')
                            ->paginate(10);
        
        return response()->json($transactions);
    }
    
    /**
     * Create a new transaction
     */
    public function createTransaction(Request $request)
    {
        $request->validate([
            'finance_category_id' => 'required|exists:finance_categories,id',
            'finance_project_id' => 'nullable|exists:finance_projects,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        
        // Verify the project belongs to the user
        if ($request->filled('finance_project_id')) {
            $project = FinanceProject::find($request->finance_project_id);
            if (!$project || $project->user_id !== Auth::id()) {
                return response()->json(['message' => 'Invalid project ID'], 422);
            }
        }
        
        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'finance_category_id' => $request->finance_category_id,
            'finance_project_id' => $request->finance_project_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
        ]);
        
        return response()->json($transaction, 201);
    }
    
    /**
     * Update a transaction
     */
    public function updateTransaction(Request $request, Transaction $transaction)
    {
        // Check if the user owns the transaction
        if ($transaction->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'finance_category_id' => 'required|exists:finance_categories,id',
            'finance_project_id' => 'nullable|exists:finance_projects,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        
        // Verify the project belongs to the user if provided
        if ($request->filled('finance_project_id')) {
            $project = FinanceProject::find($request->finance_project_id);
            if (!$project || $project->user_id !== Auth::id()) {
                return response()->json(['message' => 'Invalid project ID'], 422);
            }
        }
        
        $transaction->update([
            'finance_category_id' => $request->finance_category_id,
            'finance_project_id' => $request->finance_project_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
        ]);
        
        return response()->json($transaction);
    }
    
    /**
     * Delete a transaction
     */
    public function deleteTransaction(Transaction $transaction)
    {
        // Check if the user owns the transaction
        if ($transaction->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $transaction->delete();
        
        return response()->json(['message' => 'Transaction deleted successfully']);
    }
    
    /**
     * Get financial summary
     */
    public function getSummary(Request $request)
    {
        // Apply filters
        $query = Transaction::where('user_id', Auth::id());
        
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }
        
        if ($request->filled('project_id')) {
            $query->where('finance_project_id', $request->project_id);
        }
        
        // Clone the query for multiple uses
        $queryForIncome = clone $query;
        $queryForExpense = clone $query;
        
        // Calculate overall summary
        $totalIncome = $queryForIncome->where('type', 'income')->sum('amount');
        $totalExpense = $queryForExpense->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        
        // Get breakdown by category
        $incomeByCategory = Transaction::with('category')
                            ->where('user_id', Auth::id())
                            ->where('type', 'income')
                            ->when($request->filled('start_date'), function ($q) use ($request) {
                                return $q->where('transaction_date', '>=', $request->start_date);
                            })
                            ->when($request->filled('end_date'), function ($q) use ($request) {
                                return $q->where('transaction_date', '<=', $request->end_date);
                            })
                            ->when($request->filled('project_id'), function ($q) use ($request) {
                                return $q->where('finance_project_id', $request->project_id);
                            })
                            ->select('finance_category_id', DB::raw('SUM(amount) as amount'))
                            ->groupBy('finance_category_id')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'category' => $item->category->name,
                                    'color' => $item->category->color,
                                    'amount' => $item->amount
                                ];
                            });
        
        $expensesByCategory = Transaction::with('category')
                            ->where('user_id', Auth::id())
                            ->where('type', 'expense')
                            ->when($request->filled('start_date'), function ($q) use ($request) {
                                return $q->where('transaction_date', '>=', $request->start_date);
                            })
                            ->when($request->filled('end_date'), function ($q) use ($request) {
                                return $q->where('transaction_date', '<=', $request->end_date);
                            })
                            ->when($request->filled('project_id'), function ($q) use ($request) {
                                return $q->where('finance_project_id', $request->project_id);
                            })
                            ->select('finance_category_id', DB::raw('SUM(amount) as amount'))
                            ->groupBy('finance_category_id')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'category' => $item->category->name,
                                    'color' => $item->category->color,
                                    'amount' => $item->amount
                                ];
                            });
        
        // Get daily trends over time
        $trends = Transaction::where('user_id', Auth::id())
                ->when($request->filled('start_date'), function ($q) use ($request) {
                    return $q->where('transaction_date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function ($q) use ($request) {
                    return $q->where('transaction_date', '<=', $request->end_date);
                })
                ->when($request->filled('project_id'), function ($q) use ($request) {
                    return $q->where('finance_project_id', $request->project_id);
                })
                ->selectRaw('transaction_date as date, 
                            SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income,
                            SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
                ->groupBy('transaction_date')
                ->orderBy('transaction_date')
                ->get();
        
        return response()->json([
            'overview' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $balance
            ],
            'income_by_category' => $incomeByCategory,
            'expenses_by_category' => $expensesByCategory,
            'daily_trends' => $trends
        ]);
    }
}