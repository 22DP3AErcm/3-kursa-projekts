<template>
    <div class="finance-detail-container" v-if="project">
      <div class="finance-header">
        <h1>{{ project.name }}</h1>
        <p class="description">{{ project.description }}</p>
        
        <div class="summary-cards">
          <div class="summary-card income">
            <h3>Total Income</h3>
            <div class="amount">€{{ formatAmount(summary.overview.income) }}</div>
          </div>
          <div class="summary-card expense">
            <h3>Total Expenses</h3>
            <div class="amount">€{{ formatAmount(summary.overview.expense) }}</div>
          </div>
          <div class="summary-card balance" :class="{ 'negative': (Number(summary.overview.balance) || 0) < 0 }">
            <h3>Balance</h3>
            <div class="amount">€{{ formatAmount(summary.overview.balance) }}</div>
          </div>
        </div>
        
        <div class="tab-navigation">
          <button 
            :class="['tab-btn', { active: activeTab === 'transactions' }]" 
            @click="activeTab = 'transactions'"
          >
            Transactions
          </button>
          <button 
            :class="['tab-btn', { active: activeTab === 'graphs' }]" 
            @click="activeTab = 'graphs'"
          >
            Visualizations
          </button>
        </div>
      </div>
      
      <!-- Transactions Tab -->
      <div v-if="activeTab === 'transactions'" class="tab-content">
        <div class="transaction-controls">
          <button class="add-btn" @click="showAddTransactionModal = true">Add Transaction</button>
          
          <div class="filter-controls">
            <div class="filter-group">
              <label>Date Range:</label>
              <input type="date" v-model="filters.startDate" @change="applyFilters">
              <span>to</span>
              <input type="date" v-model="filters.endDate" @change="applyFilters">
            </div>
            
            <div class="filter-group">
              <label>Type:</label>
              <select v-model="filters.type" @change="applyFilters">
                <option value="">All</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
            </div>
            
            <div class="filter-group">
              <label>Category:</label>
              <select v-model="filters.categoryId" @change="applyFilters">
                <option value="">All Categories</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="transactions-list">
          <table v-if="transactions.length > 0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="transaction in transactions" :key="transaction.id" :class="transaction.type">
                <td>{{ formatDate(transaction.transaction_date) }}</td>
                <td>{{ transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1) }}</td>
                <td>
                  <span class="category-badge" :style="{ backgroundColor: transaction.category.color }">
                    {{ transaction.category.name }}
                  </span>
                </td>
                <td class="amount">€{{ transaction.amount.toFixed(2) }}</td>
                <td>{{ transaction.description || '-' }}</td>
                <td class="actions">
                  <button class="edit-btn-small" @click="editTransaction(transaction)">Edit</button>
                  <button class="delete-btn-small" @click="confirmDeleteTransaction(transaction)">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div v-else class="no-transactions">
            <p>No transactions found. Add your first transaction to start tracking.</p>
          </div>
          
          <div class="pagination" v-if="pagination.last_page > 1">
            <button 
              :disabled="pagination.current_page === 1" 
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </button>
            <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <button 
              :disabled="pagination.current_page === pagination.last_page" 
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
      
      <!-- Graphs Tab -->
      <div v-if="activeTab === 'graphs'" class="tab-content graphs-container">
        <div class="graph-controls">
          <div class="filter-group">
            <label>Date Range:</label>
            <input type="date" v-model="filters.startDate" @change="fetchSummary">
            <span>to</span>
            <input type="date" v-model="filters.endDate" @change="fetchSummary">
          </div>
          
          <div class="graph-type-selector">
            <label>Graph Type:</label>
            <select v-model="selectedGraphType" @change="updateActiveGraph">
              <option value="expense-by-category">Expenses by Category</option>
              <option value="income-by-category">Income by Category</option>
              <option value="income-vs-expense">Income vs Expenses (Over Time)</option>
              <option value="balance-trend">Balance Trend</option>
            </select>
          </div>
        </div>
        
        <div class="graph-container">
          <div class="graph-placeholder">
            <h3>{{ graphTitles[selectedGraphType] }}</h3>
            <div class="graph-area">
              <canvas ref="graphCanvas"></canvas>
              <div class="loading-indicator" v-if="isLoading">Loading data...</div>
            </div>
          </div>
        </div>
        
        <div class="graph-data-summary">
          <div v-if="selectedGraphType === 'expense-by-category'" class="data-list">
            <h3>Top Expense Categories</h3>
            <ul>
              <li v-for="category in sortedExpenseCategories" :key="category.category">
                <span class="category-name">{{ category.category }}</span>
                <span class="category-amount">€{{ category.amount.toFixed(2) }}</span>
                <span class="percentage">{{ calculatePercentage(category.amount, totalExpenses) }}%</span>
              </li>
            </ul>
          </div>
          
          <div v-if="selectedGraphType === 'income-by-category'" class="data-list">
            <h3>Top Income Categories</h3>
            <ul>
              <li v-for="category in sortedIncomeCategories" :key="category.category">
                <span class="category-name">{{ category.category }}</span>
                <span class="category-amount">€{{ category.amount.toFixed(2) }}</span>
                <span class="percentage">{{ calculatePercentage(category.amount, totalIncome) }}%</span>
              </li>
            </ul>
          </div>
          
        <div v-if="selectedGraphType === 'income-vs-expense' || selectedGraphType === 'balance-trend'" class="data-list">
            <h3>Monthly Summary</h3>
                <ul class="monthly-data">
                    <li v-for="(month, index) in monthlyTotals" :key="index">
                    <span class="month-name">{{ month.label }}</span>
                    <div class="month-amounts">
                        <span class="income">Income: €{{ formatAmount(month.income) }}</span>
                        <span class="expense">Expenses: €{{ formatAmount(month.expense) }}</span>
                        <span class="balance" :class="{ 'negative': month.balance < 0 }">
                        Balance: €{{ formatAmount(month.balance) }}
                        </span>
                    </div>
                    </li>
                </ul>
            </div>
        </div>
      </div>
      
      <!-- Add Transaction Modal -->
      <div class="modal" v-if="showAddTransactionModal">
        <div class="modal-content">
          <span class="close-btn" @click="showAddTransactionModal = false">&times;</span>
          <h2>{{ isEditingTransaction ? 'Edit Transaction' : 'Add New Transaction' }}</h2>
          
          <form @submit.prevent="saveTransaction">
            <div class="form-group">
              <label for="transaction-type">Type:</label>
              <select id="transaction-type" v-model="currentTransaction.type" required @change="filterCategoriesByType">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="transaction-category">Category:</label>
              <div class="category-selection">
                <select id="transaction-category" v-model="currentTransaction.finance_category_id" required>
                  <option v-for="category in filteredCategoriesByType" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
                <button type="button" class="add-category-btn" @click="showAddCategoryModal = true">
                  + New
                </button>
              </div>
            </div>
            
            <div class="form-group">
              <label for="transaction-amount">Amount:</label>
              <input type="number" id="transaction-amount" v-model="currentTransaction.amount" min="0.01" step="0.01" required>
            </div>
            
            <div class="form-group">
              <label for="transaction-date">Date:</label>
              <input type="date" id="transaction-date" v-model="currentTransaction.transaction_date" required>
            </div>
            
            <div class="form-group">
              <label for="transaction-description">Description:</label>
              <textarea id="transaction-description" v-model="currentTransaction.description" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
              <button type="button" class="cancel-btn" @click="showAddTransactionModal = false">Cancel</button>
              <button type="submit" class="submit-btn">{{ isEditingTransaction ? 'Update' : 'Add' }} Transaction</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Category Modal -->
      <div class="modal" v-if="showAddCategoryModal">
        <div class="modal-content">
          <span class="close-btn" @click="showAddCategoryModal = false">&times;</span>
          <h2>Add New Category</h2>
          
          <form @submit.prevent="createCategory">
            <div class="form-group">
              <label for="category-name">Category Name:</label>
              <input type="text" id="category-name" v-model="newCategory.name" required>
            </div>
            
            <div class="form-group">
              <label for="category-type">Type:</label>
              <select id="category-type" v-model="newCategory.type" required disabled>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
              <small>Type is set based on your transaction type</small>
            </div>
            
            <div class="form-group">
              <label for="category-color">Color:</label>
              <input type="color" id="category-color" v-model="newCategory.color">
            </div>
            
            <div class="form-actions">
              <button type="button" class="cancel-btn" @click="showAddCategoryModal = false">Cancel</button>
              <button type="submit" class="submit-btn">Create Category</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  import Chart from 'chart.js/auto';
  
  export default {

    watch: {
        // Add this watcher to recreate the chart when switching back to graphs tab
        activeTab(newTab) {
        if (newTab === 'graphs') {
            this.$nextTick(() => {
            // Wait for DOM update before recreating chart
            this.updateChart();
            });
        }
        }
    },
    data() {
      return {
        projectId: null,
        project: null,
        activeTab: 'transactions',
        categories: [],
        transactions: [],
        pagination: {
          current_page: 1,
          last_page: 1,
          total: 0
        },

        showAddCategoryModal: false,
          newCategory: {
            name: '',
            type: 'expense',
            color: '#42a5f5',
        },
        filters: {
          startDate: '',
          endDate: '',
          type: '',
          categoryId: ''
        },
        showAddTransactionModal: false,
        isEditingTransaction: false,
        currentTransaction: {
          id: null,
          finance_category_id: '',
          type: 'expense',
          amount: '',
          transaction_date: new Date().toISOString().split('T')[0],
          description: ''
        },
        summary: {
          overview: {
            income: 0,
            expense: 0,
            balance: 0
          },
          income_by_category: [],
          expenses_by_category: [],
          daily_trends: []
        },
        categorySummary: {},
        selectedGraphType: 'expense-by-category',
        graphTitles: {
          'expense-by-category': 'Expenses by Category',
          'income-by-category': 'Income by Category',
          'income-vs-expense': 'Income vs Expenses Over Time',
          'balance-trend': 'Balance Trend'
        },
        chart: null,
        isLoading: false,
        monthlyTotals: []
      };
    },
    
    computed: {
      filteredCategoriesByType() {
        return this.categories.filter(c => c.type === this.currentTransaction.type);
      },
      
      sortedExpenseCategories() {
        return [...this.summary.expenses_by_category].sort((a, b) => b.amount - a.amount);
      },
      
      sortedIncomeCategories() {
        return [...this.summary.income_by_category].sort((a, b) => b.amount - a.amount);
      },
      
      totalExpenses() {
        return this.summary.overview.expense;
      },
      
      totalIncome() {
        return this.summary.overview.income;
      }
    },
    
    mounted() {
      this.projectId = this.$route.params.id;
      this.initializeData();
    },
    
    methods: {
      async initializeData() {
        await this.fetchProject();
        await Promise.all([
          this.fetchCategories(),
          this.fetchTransactions(),
          this.fetchSummary()
        ]);
      },

      // Add this new method to create a category
      async createCategory() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          // Set type based on current transaction type
          this.newCategory.type = this.currentTransaction.type;
          
          const response = await axios.post('http://localhost:8000/api/finance/categories', 
            this.newCategory, 
            {
              headers: {
                'Authorization': `Bearer ${token}`
              }
            }
          );
          
          // Add the new category to the categories list
          this.categories.push(response.data);
          
          // Select the newly created category in the transaction form
          this.currentTransaction.finance_category_id = response.data.id;
          
          // Reset and close the modal
          this.newCategory = {
            name: '',
            type: 'expense',
            color: '#42a5f5',
          };
          this.showAddCategoryModal = false;
          
        } catch (error) {
          console.error('Error creating category:', error);
        }
      },
  
      // Format amount helper method
      formatAmount(value) {
        // Convert to number and handle null/undefined values
        const num = Number(value) || 0;
        return num.toFixed(2);
        },
      
      async fetchProject() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          const response = await axios.get(`http://localhost:8000/api/finance/projects/${this.projectId}`, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          this.project = response.data;
        } catch (error) {
          console.error('Error fetching project:', error);
        }
      },
      
      async fetchCategories() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          const response = await axios.get('http://localhost:8000/api/finance/categories', {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          this.categories = response.data;
          
          // Set default category for transaction
          if (this.categories.length > 0 && this.currentTransaction.type === 'expense') {
            const defaultCategory = this.categories.find(c => c.type === 'expense');
            if (defaultCategory) {
              this.currentTransaction.finance_category_id = defaultCategory.id;
            }
          }
        } catch (error) {
          console.error('Error fetching categories:', error);
        }
      },
      
      async fetchTransactions(page = 1) {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          let url = `http://localhost:8000/api/finance/transactions?page=${page}&project_id=${this.projectId}`;
          
          // Add filters if they exist
          if (this.filters.startDate) {
            url += `&start_date=${this.filters.startDate}`;
          }
          if (this.filters.endDate) {
            url += `&end_date=${this.filters.endDate}`;
          }
          if (this.filters.type) {
            url += `&type=${this.filters.type}`;
          }
          if (this.filters.categoryId) {
            url += `&category_id=${this.filters.categoryId}`;
          }
          
          const response = await axios.get(url, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          this.transactions = response.data.data;
          this.pagination = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total
          };
        } catch (error) {
          console.error('Error fetching transactions:', error);
        }
      },
      
      async fetchSummary() {
        this.isLoading = true;
        
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          this.isLoading = false;
          return;
        }
        
        try {
          let url = `http://localhost:8000/api/finance/summary?project_id=${this.projectId}`;
          
          // Add date filters if they exist
          if (this.filters.startDate) {
            url += `&start_date=${this.filters.startDate}`;
          }
          if (this.filters.endDate) {
            url += `&end_date=${this.filters.endDate}`;
          }
          
          const response = await axios.get(url, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          // Ensure overview values are numbers
          this.summary = {
            ...response.data,
            overview: {
              income: Number(response.data.overview.income) || 0,
              expense: Number(response.data.overview.expense) || 0,
              balance: Number(response.data.overview.balance) || 0
            }
          };
          
          // Process category summary data
          this.processCategorySummary();
          
          // Process monthly totals from daily trends
          this.processMonthlyTotals();
          
          // Update chart
          this.updateChart();
        } catch (error) {
          console.error('Error fetching summary:', error);
        } finally {
          this.isLoading = false;
        }
      },
      
      processCategorySummary() {
        // Create a summary for each category
        const categorySummary = {};
        
        // Process expense categories
        this.summary.expenses_by_category.forEach(category => {
          const categoryId = this.categories.find(c => c.name === category.category)?.id;
          if (categoryId) {
            categorySummary[categoryId] = {
              total: category.amount,
              count: 0 // We don't have this information from the summary
            };
          }
        });
        
        // Process income categories
        this.summary.income_by_category.forEach(category => {
          const categoryId = this.categories.find(c => c.name === category.category)?.id;
          if (categoryId) {
            categorySummary[categoryId] = {
              total: category.amount,
              count: 0 // We don't have this information from the summary
            };
          }
        });
        
        this.categorySummary = categorySummary;
      },
      
      processMonthlyTotals() {
        if (!this.summary.daily_trends || this.summary.daily_trends.length === 0) {
            this.monthlyTotals = [];
            return;
        }
        
        // Group by month
        const monthlyData = {};
        
        this.summary.daily_trends.forEach(day => {
            const date = new Date(day.date);
            const monthKey = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
            
            if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = {
                income: 0,
                expense: 0,
                date: date
            };
            }
            
            // Make sure to convert values to numbers
            monthlyData[monthKey].income += Number(day.income) || 0;
            monthlyData[monthKey].expense += Number(day.expense) || 0;
        });
        
        // Convert to array and calculate balance
        this.monthlyTotals = Object.entries(monthlyData)
            .map(([key, data]) => {
            return {
                key,
                label: data.date.toLocaleDateString('default', { month: 'long', year: 'numeric' }),
                income: Number(data.income) || 0,
                expense: Number(data.expense) || 0,
                balance: Number(data.income - data.expense) || 0
            };
            })
            .sort((a, b) => a.key.localeCompare(b.key));
        },
      
      updateChart() {
        // Destroy existing chart if it exists
        if (this.chart) {
          this.chart.destroy();
        }
        
        // Create new chart based on selected type
        this.createChart();
      },
      
      createChart() {
        const canvas = this.$refs.graphCanvas;
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Define chart data and options based on selected type
        let chartConfig;
        
        switch (this.selectedGraphType) {
          case 'expense-by-category':
            chartConfig = this.createPieChart(
              this.summary.expenses_by_category, 
              'Expenses by Category'
            );
            break;
          
          case 'income-by-category':
            chartConfig = this.createPieChart(
              this.summary.income_by_category, 
              'Income by Category'
            );
            break;
          
          case 'income-vs-expense':
            chartConfig = this.createLineChart(
              'Income vs Expenses Over Time'
            );
            break;
          
          case 'balance-trend':
            chartConfig = this.createBalanceChart(
              'Balance Trend'
            );
            break;
          
          default:
            chartConfig = this.createPieChart(
              this.summary.expenses_by_category, 
              'Expenses by Category'
            );
        }
        
        // Create the chart
        this.chart = new Chart(ctx, chartConfig);
      },
      
      createPieChart(data, title) {
        return {
            type: 'pie',
            data: {
                labels: data.map(item => item.category),
                datasets: [{
                    data: data.map(item => item.amount),
                    backgroundColor: data.map(item => item.color),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                    top: 0,
                    bottom: 0,  // Increased bottom padding for legend
                    left: 30,
                    right: 30
                    }
                },
                plugins: {
                    legend: {
                    position: 'bottom',
                    align: 'center',
                    labels: {
                        color: '#fff',
                        boxWidth: 12,
                        padding: 10,
                        font: {
                        size: 11
                        }
                    }
                    },
                    title: {
                    display: false
                    }
                },
                radius: '100%', // Increased from 35% to 60%
                cutout: '0%',
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        };
        },
      
      createLineChart(title) {
        // Group data by month for better visualization
        const months = this.monthlyTotals.map(month => month.label);
        const incomeData = this.monthlyTotals.map(month => month.income);
        const expenseData = this.monthlyTotals.map(month => month.expense);
        
        return {
          type: 'line',
          data: {
            labels: months,
            datasets: [
              {
                label: 'Income',
                data: incomeData,
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.3,
                fill: true
              },
              {
                label: 'Expenses',
                data: expenseData,
                borderColor: '#F44336',
                backgroundColor: 'rgba(244, 67, 54, 0.1)',
                tension: 0.3,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                  color: '#fff'
                }
              },
              x: {
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                  color: '#fff'
                }
              }
            },
            plugins: {
              legend: {
                labels: {
                  color: '#fff'
                }
              },
              title: {
                display: true,
                text: title,
                color: '#fff'
              }
            }
          }
        };
      },
      
      createBalanceChart(title) {
        // Group data by month for better visualization
        const months = this.monthlyTotals.map(month => month.label);
        const balanceData = this.monthlyTotals.map(month => month.balance);
        
        return {
          type: 'line',
          data: {
            labels: months,
            datasets: [
              {
                label: 'Balance',
                data: balanceData,
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                tension: 0.3,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                  color: '#fff'
                }
              },
              x: {
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                  color: '#fff'
                }
              }
            },
            plugins: {
              legend: {
                labels: {
                  color: '#fff'
                }
              },
              title: {
                display: true,
                text: title,
                color: '#fff'
              }
            }
          }
        };
      },
      
      changePage(page) {
        this.fetchTransactions(page);
      },
      
      applyFilters() {
        this.fetchTransactions(1); // Reset to first page when applying filters
      },
      
      updateActiveGraph() {
        this.updateChart();
      },
      
      filterCategoriesByType() {
        // When transaction type changes, update the new category type
        this.newCategory.type = this.currentTransaction.type;
        // When transaction type changes, update the selected category
        const defaultCategory = this.categories.find(c => c.type === this.currentTransaction.type);
        if (defaultCategory) {
          this.currentTransaction.finance_category_id = defaultCategory.id;
        } else {
          this.currentTransaction.finance_category_id = '';
        }
      },
      
      async saveTransaction() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          const transactionData = {
            ...this.currentTransaction,
            finance_project_id: this.projectId
          };
          
          let response;
          
          if (this.isEditingTransaction) {
            // Update existing transaction
            response = await axios.put(
              `http://localhost:8000/api/finance/transactions/${this.currentTransaction.id}`, 
              transactionData, 
              {
                headers: {
                  'Authorization': `Bearer ${token}`
                }
              }
            );
          } else {
            // Create new transaction
            response = await axios.post(
              'http://localhost:8000/api/finance/transactions', 
              transactionData, 
              {
                headers: {
                  'Authorization': `Bearer ${token}`
                }
              }
            );
          }
          
          // Reset form and close modal
          this.resetTransactionForm();
          this.showAddTransactionModal = false;
          
          // Refresh data
          await Promise.all([
            this.fetchTransactions(this.pagination.current_page),
            this.fetchSummary()
          ]);
          
        } catch (error) {
          console.error('Error saving transaction:', error);
        }
      },
      
      editTransaction(transaction) {
        this.isEditingTransaction = true;
        this.currentTransaction = {
          id: transaction.id,
          finance_category_id: transaction.finance_category_id,
          type: transaction.type,
          amount: transaction.amount,
          transaction_date: transaction.transaction_date,
          description: transaction.description || ''
        };
        
        this.showAddTransactionModal = true;
      },
      
      resetTransactionForm() {
        this.isEditingTransaction = false;
        this.currentTransaction = {
          id: null,
          finance_category_id: '',
          type: 'expense',
          amount: '',
          transaction_date: new Date().toISOString().split('T')[0],
          description: ''
        };
        
        // Set default category
        if (this.categories.length > 0) {
          const defaultCategory = this.categories.find(c => c.type === 'expense');
          if (defaultCategory) {
            this.currentTransaction.finance_category_id = defaultCategory.id;
          }
        }
      },
      
      confirmDeleteTransaction(transaction) {
        if (confirm(`Are you sure you want to delete this transaction?`)) {
          this.deleteTransaction(transaction.id);
        }
      },
      
      async deleteTransaction(transactionId) {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          await axios.delete(`http://localhost:8000/api/finance/transactions/${transactionId}`, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          // Refresh data
          await Promise.all([
            this.fetchTransactions(this.pagination.current_page),
            this.fetchSummary()
          ]);
          
        } catch (error) {
          console.error('Error deleting transaction:', error);
        }
      },
      
      formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('default', { year: 'numeric', month: 'short', day: 'numeric' });
      },
      
      calculatePercentage(amount, total) {
        if (total === 0) return 0;
        return Math.round((amount / total) * 100);
      }
      
    }
    
  };
  </script>
  
  <style scoped>
  @import '../assets/graphs.css';
  </style>