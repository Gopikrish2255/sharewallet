// Function to add expense
document.getElementById('addExpenseForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'add');

    fetch('manage_expenses.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
    })
    .then(data => {
        showAlert('Expense added successfully!', 'success');
        location.reload(); // Reload the page to update expenses
    })
    .catch(error => {
        showAlert('Failed to add expense. Please try again.', 'error');
        console.error('Error:', error);
    });
});

// Function to delete expense
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        const expenseId = this.getAttribute('data-id');
        const confirmDelete = confirm('Are you sure you want to delete this expense?');
        if (!confirmDelete) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('expenseId', expenseId);

        fetch('manage_expenses.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(data => {
            showAlert('Expense deleted successfully!', 'success');
            location.reload(); // Reload the page to update expenses
        })
        .catch(error => {
            showAlert('Failed to delete expense. Please try again.', 'error');
            console.error('Error:', error);
        });
    });
});

// Function to show alerts
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type} alert-dismissible fade show`;
    alertBox.role = 'alert';
    alertBox.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
    
    // Append alert box to container
    alertContainer.appendChild(alertBox);

    // Automatically remove alert after 3 seconds
    setTimeout(() => {
        alertBox.classList.remove('show');
        setTimeout(() => alertBox.remove(), 300); // Remove alert from DOM after fade-out
    }, 3000);
}
