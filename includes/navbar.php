<?php
// Ensure this file is not accessed directly
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}
?>
<div class="header">  
    <div class="header__logo">
        <strong>ShareWallet</strong>
    </div>
    <nav class="navbar">
        <ul class="navbar__menu">
            <li class="navbar__item">
                <a href="dashboard.php" class="navbar__link"><i data-feather="grid"></i><span>Dashboard</span></a>
            </li>
            <li class="navbar__item">
                <a href="#" class="navbar__link"><i data-feather="dollar-sign"></i><span>Expenses</span></a>
                <ul class="navbar__submenu">
                    
                    <li><a href="manage_expenses.php" class="navbar__link">Manage</a></li>
                    
                    <li><a href="add_category.php" class="navbar__link">Categories</a></li>
                </ul>
            </li>
            <li class="navbar__item">
                <a href="#" class="navbar__link"><i data-feather="pie-chart"></i><span>Budget</span></a>
                <ul class="navbar__submenu">
                    <li><a href="edit_budget.php" class="navbar__link">View</a></li>
                    <li><a href="budget_analysis.php" class="navbar__link">Analysis</a></li>
                </ul>
            </li>
            <li class="navbar__item">
    <a href="#" class="navbar__link"><i data-feather="link"></i><span>Family</span></a>
    <ul class="navbar__submenu">
 
        <li><a href="family_groups.php" class="navbar__link">Groups</a></li>
        <li><a href="expense_statistics.php" class="navbar__link">Statistics</a></li>
    </ul>
</li>

            <li class="navbar__item">
                <a href="#" class="navbar__link"><i data-feather="user"></i><span>Profile</span></a>
                <ul class="navbar__submenu">
                    <li><a href="inbox.php" class="navbar__link">Inbox</a></li>
                    <li><a href="account_edit.php" class="navbar__link">Edit</a></li>
                </ul>
            </li>
            <li class="navbar__item">
    
    </a>
</li>
            <li class="navbar__item">
                <a href="logout.php" class="navbar__link"><i data-feather="log-out"></i><span>Logout</span></a>
            </li>
        </ul>
    </nav>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@700&display=swap');
    :root {
        --primary-color: #406ff3;
        --text-color: #ffffff;
        --background-color: rgba(255, 255, 255, 0.1);
        --hover-color: rgba(255, 255, 255, 0.2);
        --transition-speed: 0.3s;
    }

    body {
        background: linear-gradient(45deg, #a8c0ff 20%, #3f2b96 80%);
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
    }

    .header {
        position: fixed;
        top: 20px;
        left: 20px;
        bottom: 20px;
        width: 220px;
        background: var(--background-color);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: var(--text-color);
        padding: 1.5rem 0.75rem;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
        transition: all var(--transition-speed) ease;
    }

    .header:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 48px 0 rgba(31, 38, 135, 0.5);
    }

    .header__logo {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--text-color);
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        transition: all var(--transition-speed) ease;
    }

    .header__logo:hover {
        transform: scale(1.05);
        text-shadow: 3px 3px 6px rgba(0,0,0,0.2);
    }

    .navbar {
        flex-grow: 1;
    }

    .navbar__menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .navbar__item {
        margin: 0.5rem 0;
        position: relative;
    }

    .navbar__link {
        color: var(--text-color);
        text-decoration: none;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        border-radius: 12px;
        transition: all var(--transition-speed) ease;
        position: relative;
        overflow: hidden;
    }

    .navbar__link i {
        min-width: 24px;
        height: 24px;
        margin-right: 0.75rem;
        transition: all var(--transition-speed) ease;
    }

    .navbar__link span {
        position: relative;
        z-index: 2;
        font-size: 0.9rem;
        white-space: nowrap;
        opacity: 0.9;
    }

    .navbar__link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--hover-color);
        opacity: 0;
        transform: translateX(-100%);
        transition: all var(--transition-speed) ease;
    }

    .navbar__link:hover::before,
    .navbar__link.active::before {
        transform: translateX(0);
        opacity: 1;
    }

    .navbar__link:hover {
        transform: translateX(5px);
    }

    .navbar__link:hover i {
        transform: scale(1.2) rotate(5deg);
    }

    .navbar__link:hover span {
        opacity: 1;
    }

    .navbar__submenu {
        max-height: 0;
        overflow: hidden;
        list-style: none;
        padding: 0;
        margin-top: 0.25rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        transition: all var(--transition-speed) ease;
    }

    .navbar__item.open .navbar__submenu {
        max-height: 300px;
        padding: 0.25rem 0;
    }

    .navbar__submenu li a {
        padding: 0.5rem 1rem 0.5rem 2.75rem;
        opacity: 0.8;
        font-size: 0.85em;
    }

    .navbar__submenu li a:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.1);
    }

    .navbar__item.open > .navbar__link::after {
        content: '';
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background-color: var(--text-color);
        border-radius: 50%;
        opacity: 0.8;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            transform: translateY(-50%) scale(0.9);
            opacity: 0.8;
        }
        50% {
            transform: translateY(-50%) scale(1.2);
            opacity: 1;
        }
        100% {
            transform: translateY(-50%) scale(0.9);
            opacity: 0.8;
        }
    }

    .navbar__link.active {
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .header {
            width: calc(100% - 40px);
            height: auto;
            position: static;
            margin: 20px;
        }

        .navbar__link {
            padding: 1rem;
        }

        .navbar__submenu {
            position: static;
            background: transparent;
        }

        .navbar__submenu li a {
            padding-left: 2.75rem;
        }
    }

    /* Custom scrollbar styles */
    .header::-webkit-scrollbar {
        width: 5px;
    }

    .header::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .header::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 5px;
    }

    .header::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
</style>

<script src="https://unpkg.com/feather-icons"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Feather icons
        feather.replace();

        // Add active class to current page link
        const currentPage = window.location.pathname.split("/").pop();
        const links = document.querySelectorAll('.navbar__link');
        links.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });

        // Toggle submenu and add open class to parent item
        const menuItems = document.querySelectorAll('.navbar__item');
        menuItems.forEach(item => {
            const link = item.querySelector('.navbar__link');
            const submenu = item.querySelector('.navbar__submenu');
            
            if (submenu) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    item.classList.toggle('open');
                    
                    // Close other open submenus
                    menuItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('open')) {
                            otherItem.classList.remove('open');
                        }
                    });
                });
            }
        });

        // Add hover effect to parent items when submenu is hovered
        const submenuItems = document.querySelectorAll('.navbar__submenu');
        submenuItems.forEach(submenu => {
            submenu.addEventListener('mouseenter', () => {
                submenu.parentElement.querySelector('.navbar__link').classList.add('hover');
            });
            submenu.addEventListener('mouseleave', () => {
                submenu.parentElement.querySelector('.navbar__link').classList.remove('hover');
            });
        });
    });
</script>