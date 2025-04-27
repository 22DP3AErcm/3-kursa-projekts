<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinanceCategory;

class FinanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Expense Categories
        $expenseCategories = [
            ['name' => 'Groceries', 'color' => '#4CAF50', 'icon' => 'shopping_cart', 'type' => 'expense'],
            ['name' => 'Rent/Mortgage', 'color' => '#2196F3', 'icon' => 'home', 'type' => 'expense'],
            ['name' => 'Utilities', 'color' => '#FFC107', 'icon' => 'bolt', 'type' => 'expense'],
            ['name' => 'Transportation', 'color' => '#9C27B0', 'icon' => 'directions_car', 'type' => 'expense'],
            ['name' => 'Entertainment', 'color' => '#E91E63', 'icon' => 'movie', 'type' => 'expense'],
            ['name' => 'Dining Out', 'color' => '#FF5722', 'icon' => 'restaurant', 'type' => 'expense'],
            ['name' => 'Healthcare', 'color' => '#00BCD4', 'icon' => 'local_hospital', 'type' => 'expense'],
            ['name' => 'Education', 'color' => '#3F51B5', 'icon' => 'school', 'type' => 'expense'],
            ['name' => 'Other Expenses', 'color' => '#607D8B', 'icon' => 'more_horiz', 'type' => 'expense'],
        ];

        // Default Income Categories
        $incomeCategories = [
            ['name' => 'Salary', 'color' => '#4CAF50', 'icon' => 'work', 'type' => 'income'],
            ['name' => 'Freelance', 'color' => '#2196F3', 'icon' => 'laptop', 'type' => 'income'],
            ['name' => 'Investments', 'color' => '#FFC107', 'icon' => 'trending_up', 'type' => 'income'],
            ['name' => 'Gifts', 'color' => '#E91E63', 'icon' => 'card_giftcard', 'type' => 'income'],
            ['name' => 'Other Income', 'color' => '#607D8B', 'icon' => 'more_horiz', 'type' => 'income'],
        ];

        // Create system categories for all users
        foreach ([...$expenseCategories, ...$incomeCategories] as $category) {
            FinanceCategory::create([
                'user_id' => 1, // System user
                'name' => $category['name'],
                'color' => $category['color'],
                'icon' => $category['icon'],
                'type' => $category['type'],
                'is_system' => true,
            ]);
        }
    }
}