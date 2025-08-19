<?php 
session_start();
define('INCLUDED', true);
require_once 'includes/database.php';
require_once 'includes/functions.php';


// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}


// Retrieve user ID from session
$userId = $_SESSION['user_id'];

// Fetch user data
$totalExpenses = getTotalExpenses($userId);
$last7DaysExpenses = getLastSevenDaysExpenses($userId);
$todayExpenses = getTodayExpenses($userId);
$monthlyBudget = getMonthlyBudget($userId);
$monthlyExpenses = getMonthlyExpenses($userId);
$categoryExpenses = getCategoryExpenses($userId);
$last7DaysData = getLast7DaysExpenses($userId);
$currentMonthSummary = getCurrentMonthSummary($userId);

$reminderMessage = checkBudgetUpdateReminder($currentUserId);

$categoryLabels = array_keys($categoryExpenses);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/ScrollTrigger.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

    :root {
  --primary-color: #6c5ce7;
  --secondary-color: #a29bfe;
  --accent-color: #00cec9;
  --text-color: #2d3436;
  --background-color: #f9f9f9;
  --card-background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  --chart-background: rgba(255, 255, 255, 0.8);
  --shadow-color: rgba(108, 92, 231, 0.2);
}

.dark-mode {
  --primary-color:rgb(255, 255, 255);
  --secondary-color: #b0a8fd;
  --accent-color:rgb(2, 80, 182);
  --text-color: #ecf0f1;
  --background-color: #1a1a2e;
  --card-background: linear-gradient(135deg, #4834d4, #686de0);
  --chart-background: rgba(5, 5, 5, 0.8);
  --shadow-color: rgba(0, 0, 0, 0.3);
}

body {
  background: var(--background-color);
  font-family: 'Poppins', sans-serif;
  display: flex;
  min-height: 100vh;
  color: var(--text-color);
  transition: all 0.3s ease;
  margin: 0;
}

.main-content {
  flex-grow: 1;
  padding: 2rem;
  margin-left: 16rem;
  width: calc(100% - 16rem);
  overflow-y: auto;
}

.container {
  background: var(--chart-background);
  border-radius: 20px;
  box-shadow: 0 10px 30px var(--shadow-color);
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
}

h1 {
  font-size: 2.5rem;
  margin-bottom: 1.5rem;
  color: var(--primary-color);
}



.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.dashboard-card {
  background: var(--card-background);
  color: white;
  padding: 1.5rem;
  border-radius: 15px;
  box-shadow: 0 4px 20px var(--shadow-color);
  transition: all 0.3s ease;
}

.dashboard-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 25px var(--shadow-color);
}

.dashboard-card h3 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
  font-weight: 400;
}

.dashboard-card p {
  font-size: 1.5rem;
  font-weight: 600;
}
.monthly-budget-progress {
  height: 18px;
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  margin-top: 1rem;
  overflow: hidden;
  position: relative;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.monthly-budget-progress:before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.5));
  animation: shine 2s ease infinite;
}

.monthly-budget-progress .progress-bar {
  height: 100%;
  background-image: linear-gradient(to right, #e74c3c, #d50253);
  border-radius: 6px;
  transition: width 0.8s ease;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding-right: 6px;
}

.monthly-budget-progress .progress-text {
  color: white;
  font-size: 0.1rem;
  font-weight: 300;
  font-family: 'Roboto', sans-serif;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  white-space: nowrap;
}

@keyframes shine {
  0% {
    background-position: -100% 0;
  }
  100% {
    background-position: 100% 0;
  }
}
.charts-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-container {
  flex: 1 1 calc(33.333% - 1rem);
  min-width: 300px;
  background: var(--chart-background);
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 5px 20px var(--shadow-color);
  height: 350px;
  display: flex;
  flex-direction: column;
  transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
  transform-style: preserve-3d;
  position: relative;
  overflow: hidden;
}

.chart-container:hover {
  transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
  box-shadow: 0 15px 40px var(--shadow-color);
}

.chart-container h2 {
  font-size: 1.2rem;
  margin-bottom: 1rem;
  color: var(--primary-color);
  flex-shrink: 0;
  transform: translateZ(20px);
  position: relative;
  z-index: 1;
}

.chart-container canvas {
  flex-grow: 1;
  width: 100% !important;
  height: 100% !important;
  max-height: calc(100% - 2.5rem);
  transform: translateZ(30px);
  position: relative;
  z-index: 1;
  transition: all 0.3s ease;
}

.chart-container:hover canvas {
  transform: translateZ(40px) scale(1.05);
}

.header {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 16rem;
  background: var(--primary-color);
  color: white;
  padding: 2rem 1.5rem;
  box-shadow: 5px 0 30px var(--shadow-color);
  display: flex;
  flex-direction: column;
  z-index: 1000;
  transition: all 0.3s ease;
}

.mode-toggle {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 1001;
}

.toggle {
  height: 34px;
  width: 68px;
  border-radius: 17px;
  background-color: var(--chart-background);
  box-shadow:
    inset 0 -2px 2px 0 rgba(0,0,0,0.2),
    inset 0 2px 2px 0 rgba(255,255,255,0.2),
    0 2px 5px 0 rgba(0,0,0,0.2);
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.switch {
  height: 26px;
  width: 26px;
  border-radius: 50%;
  background: var(--primary-color);
  position: absolute;
  top: 4px;
  left: 4px;
  transition: all 0.3s ease;
  box-shadow:
    0 2px 4px rgba(0,0,0,0.2),
    inset 0 1px 1px rgba(255,255,255,0.2);
}

.toggle.dark .switch {
  transform: translateX(34px);
  background: var(--accent-color);
}

@media (max-width: 1200px) {
  .chart-container {
    flex: 1 1 calc(50% - 1rem);
  }
}

@media (max-width: 768px) {
  body { 
    flex-direction: column; 
  }
  .header {
    position: static;
    width: 100%;
    height: auto;
    padding: 1rem;
  }
  .main-content {
    margin-left: 0;
    width: 100%;
    padding: 1rem;
  }
  .charts-row {
    flex-direction: column;
  }
  .chart-container {
    flex: 1 1 100%;
    height: 300px;
    margin-bottom: 1rem;
  }
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  h1 {
    font-size: 2rem;
  }
}
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <div class="main-content">
    <div class="container">
      <div class="mode-toggle">
        <div class="toggle">
          <div class="switch"></div>
        </div>
      </div>

      <h1>Dashboard</h1>

      <div class="dashboard-grid">
        <div class="dashboard-card" id="totalExpensesCard">
          <h3>Total Expenses</h3>
          <p id="totalExpenses">₹<?php echo number_format($totalExpenses, 2); ?></p>
        </div>
        <div class="dashboard-card" id="last7DaysCard">
          <h3>Last 7 Days</h3>
          <p id="last7Days">₹<?php echo number_format($last7DaysExpenses, 2); ?></p>
        </div>
        <div class="dashboard-card" id="todayExpensesCard">
          <h3>Today's Expenses</h3>
          <p id="todayExpenses">₹<?php echo number_format($todayExpenses, 2); ?></p>
        </div>
        <div class="dashboard-card" id="monthlyBudgetCard">
  <h3>Monthly Budget</h3>
  <p id="monthlyBudget">₹<?php echo number_format($monthlyBudget, 2); ?></p>
  <div class="monthly-budget-progress">
    <div class="progress-bar">
      <span class="progress-text">0%</span>
    </div>
  </div>
</div>
      </div>

      <div class="charts-row">
        <div class="chart-container">
          <h2>Monthly Expense Breakdown</h2>
          <canvas id="monthlyExpenseChart"></canvas>
        </div>
        <div class="chart-container">
          <h2>Category Expenses</h2>
          <canvas id="categoryExpenseChart"></canvas>
        </div>
      </div>

      <div class="chart-container">
        <h2>Last 7 Days Expenses</h2>
        <canvas id="last7DaysChart"></canvas>
      </div>

      <script>
        const ctx1 = document.getElementById('monthlyExpenseChart').getContext('2d');
        const ctx2 = document.getElementById('categoryExpenseChart').getContext('2d');
        const ctx3 = document.getElementById('last7DaysChart').getContext('2d');

        const monthlyExpenses = <?php echo json_encode(array_values($monthlyExpenses)); ?>;
        const categoryExpenses = <?php echo json_encode(array_values($categoryExpenses)); ?>;
        const categoryLabels = <?php echo json_encode(array_keys($categoryExpenses)); ?>;
        const last7DaysData = <?php echo json_encode($last7DaysData); ?>;

        // Monthly Expense Chart
const monthlyExpenseChart = new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [{
      label: 'Monthly Expenses',
      data: monthlyExpenses,
      backgroundColor: 'rgba(108, 92, 231, 0.6)',
      borderColor: 'rgba(108, 92, 231, 1)',
      borderWidth: 1,
      borderRadius: 5,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
      padding: {
        top: 10,
        bottom: 10,
        left: 10,
        right: 10
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          display: false
        },
        ticks: {
          font: {
            size: 8  // Reduced font size
          }
        }
      },
      x: {
        grid: {
          display: false
        },
        ticks: {
          font: {
            size: 8  // Reduced font size
          }
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});

// Category Expense Chart (Doughnut)

const categoryExpenseChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: categoryLabels, // Use dynamic labels
        datasets: [{
            label: 'Category Expenses',
            data: categoryExpenses, // Use dynamic data
            backgroundColor: [
                'rgba(231, 76, 60, 0.6)',
                'rgba(46, 204, 113, 0.6)',
                'rgba(52, 152, 219, 0.6)',
                'rgba(155, 89, 182, 0.6)',
                'rgba(241, 196, 15, 0.6)',
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    font: {
                        size: 8 // Adjust font size
                    }
                }
            }
        }
    }
});


// Last 7 Days Expense Chart (Line)
const last7DaysChart = new Chart(ctx3, {
  type: 'line',
  data: {
    labels: Object.keys(last7DaysData),
    datasets: [{
      label: 'Last 7 Days Expenses',
      data: Object.values(last7DaysData),
      fill: false,
      borderColor: 'rgb(75, 192, 192)',
      tension: 0.1,
      pointBackgroundColor: 'rgb(75, 192, 192)',
      pointBorderColor: '#fff',
      pointHoverBackgroundColor: '#fff',
      pointHoverBorderColor: 'rgb(75, 192, 192)'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
      padding: {
        top: 10,
        bottom: 10,
        left: 10,
        right: 10
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          display: false
        },
        ticks: {
          font: {
            size: 8  // Reduced font size
          }
        }
      },
      x: {
        grid: {
          display: false
        },
        ticks: {
          font: {
            size: 8  // Reduced font size
          }
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});


        // Dark Mode Toggle
        document.addEventListener("DOMContentLoaded", function() {
    const toggle = document.querySelector(".toggle");
    const body = document.body;

    // Check local storage and apply the dark mode if it's already set
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        toggle.classList.add("dark");
    }

    // Toggle switch functionality
    toggle.addEventListener("click", function() {
        if (body.classList.contains("dark-mode")) {
            body.classList.remove("dark-mode");
            toggle.classList.remove("dark");
            localStorage.setItem("darkMode", "disabled");
        } else {
            body.classList.add("dark-mode");
            toggle.classList.add("dark");
            localStorage.setItem("darkMode", "enabled");
        }
    });
});

        function updateChartsTheme() {
          const isDarkMode = body.classList.contains('dark-mode');
          const textColor = isDarkMode ? '#ecf0f1' : '#2d3436';

          [monthlyExpenseChart, categoryExpenseChart, last7DaysChart].forEach(chart => {
            chart.options.scales.x.ticks.color = textColor;
            chart.options.scales.y.ticks.color = textColor;
            chart.update();
          });
        }

        

        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        // Dashboard cards animation
        gsap.from('.dashboard-card', {
          opacity: 0,
          y: 50,
          stagger: 0.1,
          duration: 0.8,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: '.dashboard-grid',
            start: 'top 80%',
          }
        });

        // Charts animation
        gsap.from('.chart-container', {
          opacity: 0,
          scale: 0.9,
          stagger: 0.2,
          duration: 1,
          ease: 'elastic.out(1, 0.5)',
          scrollTrigger: {
            trigger: '.charts-row',
            start: 'top 80%',
          }
        });

        // Micro-interactions for dashboard cards
        document.querySelectorAll('.dashboard-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      gsap.to(card, {
        scale: 1.05,
        duration: 0.3,
        ease: 'power2.out'
      });
    });

    card.addEventListener('mouseleave', () => {
      gsap.to(card, {
        scale: 1,
        duration: 0.3,
        ease: 'power2.out'
      });
    });

    card.addEventListener('click', () => {
      gsap.to(card, {
        scale: 0.95,
        duration: 0.1,
        ease: 'power2.in',
        yoyo: true,
        repeat: 1
      });
    });
  });

  // Function to animate number counters
  function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      obj.innerHTML = '₹' + Math.floor(progress * (end - start) + start).toLocaleString();
      if (progress < 1) {
        window.requestAnimationFrame(step);
      }
    };
    window.requestAnimationFrame(step);
  }

  // Animate dashboard card values
  function animateDashboardValues() {
    const totalExpenses = parseFloat(document.getElementById('totalExpenses').innerText.replace('₹', '').replace(',', ''));
    const last7Days = parseFloat(document.getElementById('last7Days').innerText.replace('₹', '').replace(',', ''));
    const todayExpenses = parseFloat(document.getElementById('todayExpenses').innerText.replace('₹', '').replace(',', ''));
    const monthlyBudget = parseFloat(document.getElementById('monthlyBudget').innerText.replace('₹', '').replace(',', ''));

    animateValue(document.getElementById('totalExpenses'), 0, totalExpenses, 1500);
    animateValue(document.getElementById('last7Days'), 0, last7Days, 1500);
    animateValue(document.getElementById('todayExpenses'), 0, todayExpenses, 1500);
    animateValue(document.getElementById('monthlyBudget'), 0, monthlyBudget, 1500);
  }

  function updateMonthlyBudgetCard() {
  const monthlyBudget = parseFloat(document.getElementById('monthlyBudget').innerText.replace('₹', '').replace(',', ''));
  const monthlyExpenses = parseFloat(document.getElementById('totalExpenses').innerText.replace('₹', '').replace(',', ''));

  const monthlyBudgetCard = document.getElementById('monthlyBudgetCard');
  const monthlyBudgetProgress = monthlyBudgetCard.querySelector('.monthly-budget-progress .progress-bar');
  const monthlyBudgetText = monthlyBudgetCard.querySelector('.monthly-budget-progress .progress-text');

  // Calculate the percentage of monthly budget used
  const budgetUsagePercentage = (monthlyExpenses / monthlyBudget) * 100;

  // Change the color of the monthly budget card based on the budget usage percentage
  if (budgetUsagePercentage >= 80) {
    monthlyBudgetCard.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';
  } else if (budgetUsagePercentage >= 50) {
    monthlyBudgetCard.style.background = 'linear-gradient(135deg, #f39c12, #e67e22)';
  } else {
    monthlyBudgetCard.style.background = 'var(--card-background)';
  }

  // Update the progress bar animation
  gsap.to(monthlyBudgetProgress, {
    width: `${Math.min(budgetUsagePercentage, 100)}%`,
    duration: 0.5,
    ease: 'power2.out',
    onUpdate: () => {
      monthlyBudgetText.textContent = `${Math.round(budgetUsagePercentage)}%`;
    }
  });
}

// Call the updateMonthlyBudgetCard function on page load and refresh
window.addEventListener('load', updateMonthlyBudgetCard);
refreshButton.addEventListener('click', updateMonthlyBudgetCard);
  // Animate dashboard values on page load
  window.addEventListener('load', animateDashboardValues);

  // Refresh button animation
  const refreshButton = document.createElement('button');
  refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
  refreshButton.style.position = 'absolute';
  refreshButton.style.top = '20px';
  refreshButton.style.right = '100px';
  refreshButton.style.padding = '8px 16px';
  refreshButton.style.backgroundColor = 'var(--primary-color)';
  refreshButton.style.color = 'white';
  refreshButton.style.border = 'none';
  refreshButton.style.borderRadius = '5px';
  refreshButton.style.cursor = 'pointer';
  document.querySelector('.container').appendChild(refreshButton);

  refreshButton.addEventListener('click', () => {
    gsap.to(refreshButton.querySelector('i'), {
      rotation: 360,
      duration: 1,
      ease: 'power2.inOut'
    });
    
    // Simulate data refresh (replace with actual data fetching in a real application)
    setTimeout(() => {
      animateDashboardValues();
      monthlyExpenseChart.update();
      categoryExpenseChart.update();
      last7DaysChart.update();
    }, 500);
  });

  // Chart animations
  function animateChart(chart) {
    let currentIndex = 0;
    const data = chart.data.datasets[0].data;
    chart.data.datasets[0].data = data.map(() => 0);
    chart.update();

    function animateNextDataPoint() {
      if (currentIndex < data.length) {
        chart.data.datasets[0].data[currentIndex] = data[currentIndex];
        chart.update();
        currentIndex++;
        requestAnimationFrame(animateNextDataPoint);
      }
    }

    animateNextDataPoint();
  }

  // Animate charts on scroll
  ScrollTrigger.create({
    trigger: '.charts-row',
    start: 'top 80%',
    onEnter: () => {
      animateChart(monthlyExpenseChart);
      animateChart(categoryExpenseChart);
      animateChart(last7DaysChart);
    },
    once: true
  });

  // Add responsive behavior
  function handleResponsive() {
    if (window.innerWidth <= 768) {
      document.querySelectorAll('.chart-container').forEach(container => {
        container.style.height = '200px';
      });
    } else {
      document.querySelectorAll('.chart-container').forEach(container => {
        container.style.height = '250px';
      });
    }
    monthlyExpenseChart.resize();
    categoryExpenseChart.resize();
    last7DaysChart.resize();
  }

  window.addEventListener('resize', handleResponsive);
  handleResponsive(); // Call once on load

  document.addEventListener('DOMContentLoaded', function() {
        <?php if ($reminderMessage): ?>
        Swal.fire({
            title: 'Budget Reminder',
            text: <?php echo json_encode($reminderMessage); ?>,
            icon: 'info',
            confirmButtonText: 'Update Budget',
            showCancelButton: true,
            cancelButtonText: 'Remind Me Later'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to budget update page or open modal
                window.location.href = 'update_budget.php';
            }
        });
        <?php endif; ?>
    });
</script>
</body>
</html>